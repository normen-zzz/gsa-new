<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Exception;

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
            'typeselling.created_at'
        ];
        $typesellings = DB::table('typeselling')
            ->select($select)
            ->when($search, function ($query) use ($search) {
                return $query->where('typeselling.name', 'like', '%' . $search . '%');
            })
            ->orderBy('typeselling.id_typeselling', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Type sellings retrieved successfully.', $typesellings, 200);
    }

    public function getTypesellingById($id)
    {
        $typeselling = DB::table('typeselling')
            ->where('id_typeselling', $id)
            ->first();

        if (!$typeselling) {
            return ResponseHelper::success('Type selling not found.', NULL, 404);
        }

        return ResponseHelper::success('Type selling retrieved successfully.', $typeselling, 200);
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
            return ResponseHelper::success('Type selling created successfully.', ['id_typeselling' => $insertId], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateTypeselling(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'initials' => 'sometimes|required|string|max:10|unique:typeselling,initials,' . $id . ',id_typeselling',
                'name' => 'sometimes|required|string|max:255|unique:typeselling,name,' . $id . ',id_typeselling',
                'description' => 'nullable|string|max:500',
            ]);

            $data = $request->only(['initials', 'name', 'description']);
            $data['updated_at'] = now();

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

    public function deleteTypeselling($id)
    {
        DB::beginTransaction();
        try {
            $deleted = DB::table('typeselling')
                ->where('id_typeselling', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => 1]);

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

    public function restoreTypeselling($id)
    {
        DB::beginTransaction();
        try {
            $restored = DB::table('typeselling')
                ->where('id_typeselling', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null]);

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
