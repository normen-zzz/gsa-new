<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

date_default_timezone_set('Asia/Jakarta');

class TypesellingController extends Controller
{
    public function getTypeselling(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'typeselling.id_typeselling',
            'typeselling.initials',
            'typeselling.name',
            'typeselling.description',
            'typeselling.created_at',
            'typeselling.updated_at',
            'typeselling.deleted_at',
            'typeselling.created_by',
            'typeselling.updated_by',
            'users.name as created_by_name'
        ];
        $typesellings = DB::table('typeselling')
            ->select($select)
            ->join('users', 'typeselling.created_by', '=', 'users.id_user')
            ->when($search, function ($query) use ($search) {
                return $query->where('typeselling.name', 'like', '%' . $search . '%');
            })
            ->orderBy('typeselling.id_typeselling', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Type sellings retrieved successfully.', $typesellings, 200);
    }

    

    public function createTypeselling(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'initials' => 'required|string|max:10|unique:typeselling,initials',
                'name' => 'required|string|max:255|unique:typeselling,name',
                'description' => 'nullable|string|max:500',
            ]);

            $data = $request->only(['initials', 'name', 'description']);
            $data['created_by'] = 1; // Assuming created_by is always 1 for this example
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $insertId = DB::table('typeselling')->insertGetId($data);

            DB::commit();
            return ResponseHelper::success('Type selling created successfully.',null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateTypeselling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_typeselling'); // Assuming the ID is passed in the route
            $request->validate([
                'initials' => 'sometimes|required|string|unique:typeselling,initials,' . $id . ',id_typeselling',
                'name' => 'sometimes|required|string|max:255|unique:typeselling,name,' . $id . ',id_typeselling',
                'description' => 'nullable|string|max:500',
            ]);

            $data = $request->only(['initials', 'name', 'description']);
            $data['updated_at'] = now();
            $data['updated_by'] = $request->user()->id; // Assuming updated_by is the ID of the authenticated user

            DB::table('typeselling')
                ->where('id_typeselling', $id)
                ->update($data);

            DB::commit();
            return ResponseHelper::success('Type selling updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteTypeselling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_typeselling'); // Assuming the ID is passed in the request
            $deleted = DB::table('typeselling')
                ->where('id_typeselling', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => 1, 'status' => 'inactive']);

            if ($deleted) {
                DB::commit();
                return ResponseHelper::success('Type selling deleted successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to delete type selling.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function restoreTypeselling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_typeselling'); // Assuming the ID is passed in the request
            $restored = DB::table('typeselling')
                ->where('id_typeselling', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null, 'status' => 'active']);

            if ($restored) {
                DB::commit();
                return ResponseHelper::success('Type selling restored successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to restore type selling.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
