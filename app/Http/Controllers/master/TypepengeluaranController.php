<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Helpers\ResponseHelper;

class TypepengeluaranController extends Controller
{
    public function createTypepengeluaran(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'name' => 'required|string|max:100|unique:type_pengeluaran,name',
            ]);

            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();
            $data['updated_by'] = Auth::id();

            $typepengeluaran = DB::table('type_pengeluaran')->insert($data);
            if ($typepengeluaran) {
                DB::commit();
                return ResponseHelper::success('Type Pengeluaran created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create Type Pengeluaran.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function getTypepengeluaran(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'type_pengeluaran.id_typepengeluaran',
            'type_pengeluaran.name',
            'type_pengeluaran.created_by as created_by',
            'users.name as created_by_name',
            'type_pengeluaran.created_at',
            'type_pengeluaran.updated_at',
            'type_pengeluaran.updated_by',
            'users2.name as updated_by_name',
            'type_pengeluaran.deleted_at',
            'type_pengeluaran.status'
        ];

        $query = DB::table('type_pengeluaran')
            ->leftJoin('users', 'type_pengeluaran.created_by', '=', 'users.id_user')
            ->leftJoin('users as users2', 'type_pengeluaran.updated_by', '=', 'users2.id_user') 
            ->select($select)
            ->when($search, function ($query, $search) {
                return $query->where('type_pengeluaran.name', 'like', "%$search%");
            })
            ->orderBy('type_pengeluaran.created_at', 'desc');

        $total = $query->count();
        $data = $query->paginate($limit);

        return ResponseHelper::success('List of Type Pengeluaran', $data, 200);
    }

    public function getTypepengeluaranById(Request $request)
    {
        $id = $request->id_typepengeluaran;

        $select = [
            'type_pengeluaran.id_typepengeluaran',
            'type_pengeluaran.name',
            'type_pengeluaran.created_by as created_by',
            'users.name as created_by_name',
            'type_pengeluaran.created_at',
            'type_pengeluaran.updated_at',
            'type_pengeluaran.updated_by',
            'users2.name as updated_by_name',
            'type_pengeluaran.deleted_at',
            'type_pengeluaran.status'
        ];

        $typepengeluaran = DB::table('type_pengeluaran')
            ->where('id_typepengeluaran', $id)
            ->leftJoin('users', 'type_pengeluaran.created_by', '=', 'users.id_user')
            ->leftJoin('users as users2', 'type_pengeluaran.updated_by', '=', 'users2.id_user')
            ->select($select)
            ->first();

        if ($typepengeluaran) {
            return ResponseHelper::success('Type Pengeluaran found.', $typepengeluaran, 200);
        } else {
            return ResponseHelper::success('Type Pengeluaran not found.', null, 200);
        }
    }

    public function deleteTypepengeluaran(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->id_typepengeluaran;
            $userId = Auth::id();

            $typepengeluaran = DB::table('type_pengeluaran')->where('id_typepengeluaran', $id)->first();

            if (!$typepengeluaran) {
                return ResponseHelper::error(new Exception('Type Pengeluaran not found.'), 404);
            }

            $deleted = DB::table('type_pengeluaran')
                ->where('id_typepengeluaran', $id)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => $userId,
                    'status' => 'inactive',
                ]);

            if ($deleted) {
                DB::commit();
                return ResponseHelper::success('Type Pengeluaran deleted successfully.', null, 200);
            } else {
                throw new Exception('Failed to delete Type Pengeluaran.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function updateTypepengeluaran(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->id_typepengeluaran;
            $userId = Auth::id();

            $data = $request->validate([
                'name' => 'required|string|max:100|unique:type_pengeluaran,name,' . $id . ',id_typepengeluaran',
            ]);

            $typepengeluaran = DB::table('type_pengeluaran')->where('id_typepengeluaran', $id)->first();

            if (!$typepengeluaran) {
                return ResponseHelper::error(new Exception('Type Pengeluaran not found.'), 404);
            }

            $data['updated_by'] = $userId;
            $data['updated_at'] = now();

            $updated = DB::table('type_pengeluaran')
                ->where('id_typepengeluaran', $id)
                ->update($data);

            if ($updated) {
                DB::commit();
                return ResponseHelper::success('Type Pengeluaran updated successfully.', null, 200);
            } else {
                throw new Exception('Failed to update Type Pengeluaran.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function restoreTypepengeluaran(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->id_typepengeluaran;
            $userId = Auth::id();

            $typepengeluaran = DB::table('type_pengeluaran')->where('id_typepengeluaran', $id)->first();

            if (!$typepengeluaran) {
                return ResponseHelper::error(new Exception('Type Pengeluaran not found.'), 404);
            }

            $restored = DB::table('type_pengeluaran')
                ->where('id_typepengeluaran', $id)
                ->update([
                    'deleted_at' => null,
                    'deleted_by' => null,
                ]);

            if ($restored) {
                DB::commit();
                return ResponseHelper::success('Type Pengeluaran restored successfully.', null, 200);
            } else {
                throw new Exception('Failed to restore Type Pengeluaran.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }
}
