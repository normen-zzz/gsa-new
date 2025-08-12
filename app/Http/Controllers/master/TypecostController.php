<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;

date_default_timezone_set('Asia/Jakarta');
class TypecostController extends Controller
{
    public function getTypecost(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'typecost.id_typecost',
            'typecost.initials',
            'typecost.name',
            'typecost.description',
            'typecost.created_at',
            'users.name as created_by_name',
            'typecost.updated_at',
            'typecost.status'


        ];
        $typecosts = DB::table('typecost')
            ->select($select)
            ->join('users', 'typecost.created_by', '=', 'users.id_user')
            ->when($search, function ($query) use ($search) {
                return $query->where('typecost.name', 'like', '%' . $search . '%');
            })
            ->orderBy('typecost.id_typecost', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Type costs retrieved successfully.', $typecosts, 200);
    }

    public function getTypecostById(Request $request)
    {
        $id = $request->input('id');
        $select = [
            'typecost.id_typecost',
            'typecost.initials',
            'typecost.name',
            'typecost.description',
            'typecost.created_at',
            'users.name as created_by_name',
            'typecost.updated_at',
            'typecost.status'
        ];
        $typecost = DB::table('typecost')
            ->select($select)
            ->join('users', 'typecost.created_by', '=', 'users.id_user')
            ->where('typecost.id_typecost', $id)
            ->first();

        if (!$typecost) {
            return ResponseHelper::error(new Exception('Type cost not found.'), 404);
        }

        return ResponseHelper::success('Type cost retrieved successfully.', $typecost, 200);
    }

    public function createTypecost(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'initials' => 'required|string|unique:typecost,initials',
                'name' => 'required|string|max:255|unique:typecost,name',
                'description' => 'nullable|string|max:500',
            ]);

            $data = $request->only(['initials', 'name', 'description']);
            $data['created_by'] = 1; // Assuming created_by is always 1 for this example
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $insertedId = DB::table('typecost')->insertGetId($data);
            DB::commit();
            return ResponseHelper::success('Type cost created successfully.', null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateTypecost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_typecost');

            $request->validate([
                'id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'initials' => 'sometimes|required|string|max:10|unique:typecost,initials,' . $id . ',id_typecost',
                'name' => 'sometimes|required|string|max:255|unique:typecost,name,' . $id . ',id_typecost',
                'description' => 'nullable|string|max:500',
            ]);
            $typecost = DB::table('typecost')
                ->where('id_typecost', $id)
                ->first();

            $data = $request->only(['initials', 'name', 'description']);
            $data['updated_by'] = $request->user()->id_user;
            $data['updated_at'] = now();

            $changes = [];
            foreach ($data as $key => $value) {
                if ($typecost->$key !== $value) {
                    $changes[$key] = [
                        'old' => $typecost->$key,
                        'new' => $value,
                    ];
                }
            }

            DB::table('typecost')
                ->where('id_typecost', $id)
                ->update($data);

            $logData = [
                'id_typecost' => $id,
                'action' => json_encode([
                    'type' => 'update',
                    'changes' => $changes,
                ]),
                'id_user' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('log_typecost')->insert($logData);

            DB::commit();
            return ResponseHelper::success('Type cost updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteTypecost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_typecost');
            $check = DB::table('typecost')
                ->where('id_typecost', $id)
                ->first();

            $deleted = DB::table('typecost')
                ->where('id_typecost', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id(), 'status' => 'inactive']);

            $changes = [
                'type' => 'delete',
                'old' => [
                    'status' => $check->status,
                ],
                'new' => [
                    'status' => 'inactive',
                ],
            ];

            if ($deleted) {
                $logData = [
                    'id_typecost' => $id,
                    'action' => json_encode($changes),
                    'id_user' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('log_typecost')->insert($logData);
                DB::commit();
                return ResponseHelper::success('Type cost deleted successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to delete type cost.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
    public function restoreTypecost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_typecost');
            $check = DB::table('typecost')
                ->where('id_typecost', $id)
                ->first();
            $restored = DB::table('typecost')
                ->where('id_typecost', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null, 'status' => 'active', 'updated_by' => Auth::id(), 'updated_at' => now()]);

            $changes = [
                'type' => 'restore',
                'old' => [
                    'status' => $check->status,
                ],
                'new' => [
                    'status' => 'active',
                ],
            ];
            if ($restored) {
                $logData = [
                    'id_typecost' => $id,
                    'action' => json_encode($changes),
                    'id_user' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('log_typecost')->insert($logData);
                DB::commit();
                return ResponseHelper::success('Type cost restored successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to restore type cost.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
