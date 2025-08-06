<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Exception;

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
            'typecost.created_at'
        ];
        $typecosts = DB::table('typecost')
            ->select($select)
            ->when($search, function ($query) use ($search) {
                return $query->where('typecost.name', 'like', '%' . $search . '%');
            })
            ->orderBy('typecost.id_typecost', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Type costs retrieved successfully.', $typecosts, 200);
    }

    public function getTypecostById($id)
    {
        $typecost = DB::table('typecost')
            ->where('id_typecost', $id)
            ->first();

        if (!$typecost) {
            return ResponseHelper::success('Type cost not found.', NULL, 404);
        }

        return ResponseHelper::success('Type cost retrieved successfully.', $typecost, 200);
    }

    public function createTypecost(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'initials' => 'required|string|max:10|unique:typecost,initials',
                'name' => 'required|string|max:255|unique:typecost,name',
                'description' => 'nullable|string|max:500',
            ]);

            $data = $request->only(['initials', 'name', 'description']);
            $data['created_by'] = 1; // Assuming created_by is always 1 for this example
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $insertedId = DB::table('typecost')->insertGetId($data);
            DB::commit();
            return ResponseHelper::success('Type cost created successfully.', ['id' => $insertedId], 201);
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

            $data = $request->only(['initials', 'name', 'description']);
            $data['updated_by'] = 1; // Assuming updated_by is always 1 for this example
            $data['updated_at'] = now();

            DB::table('typecost')
                ->where('id_typecost', $id)
                ->update($data);

            DB::commit();
            return ResponseHelper::success('Type cost updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteTypecost($id)
    {
        DB::beginTransaction();
        try {
            $deleted = DB::table('typecost')
                ->where('id_typecost', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => 1]);

            if ($deleted) {
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
    public function restoreTypecost($id)
    {
        DB::beginTransaction();
        try {
            $restored = DB::table('typecost')
                ->where('id_typecost', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null]);

            if ($restored) {
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
