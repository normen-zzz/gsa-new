<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

class RoleController extends Controller
{
    public function getRoles(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'roles.id_role',
            'roles.name',
            'roles.description',
            'roles.status',
            'roles.created_at',
            'divisions.id_division',
            'divisions.name as division_name'
        ];

        $roles = DB::table('roles')
            ->select($select)
            ->join('divisions', 'roles.id_division', '=', 'divisions.id_division')
            ->when($search, function ($query) use ($search) {
                return $query->where('roles.name', 'like', '%' . $search . '%');
            })
            ->orderBy('roles.id_role', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Roles retrieved successfully.', $roles->items(), 200);
    }
    public function getRoleById($id)
    {
        $select = [
            'roles.id_role',
            'roles.name',
            'roles.description',
            'roles.status',
            'roles.created_at',
            'divisions.id_division',
            'divisions.name as division_name'
        ];
        $role = DB::table('roles')
            ->select($select)
            ->join('divisions', 'roles.id_division', '=', 'divisions.id_division')
            ->where('id_role', $id)
            ->first();

        if (!$role) {
            return ResponseHelper::success('Role not found.', NULL, 404);
        }

        return ResponseHelper::success('Role retrieved successfully.', $role, 200);
    }
    public function createRole(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_division' => 'required|exists:divisions,id_division',
                'name' => 'required|string|max:255|unique:roles,name',
                'description' => 'nullable|string|max:500',
                'status' => 'required|boolean|default:true'
            ]);

            $role = DB::table('roles')->insertGetId([
                'id_division' => $request->input('id_division'),
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'status' => $request->input('status'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return ResponseHelper::success('Role created successfully.', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
    public function updateRole(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_role');
            $request->validate([
                'id_role' => 'required|exists:roles,id_role',
                'id_division' => 'required|exists:divisions,id_division',
                'name' => 'required|string|max:255|unique:roles,name,' . $id . ',id_role',
                'description' => 'nullable|string|max:500',
                'status' => 'boolean'
            ]);

            $role = DB::table('roles')
                ->where('id_role', $id)
                ->update([
                    'id_division' => $request->input('id_division'),
                    'name' => $request->input('name'),
                    'description' => $request->input('description', null),
                    'status' => $request->input('status', true),
                    'updated_at' => now()
                ]);

            if (!$role) {
                throw new Exception('Failed to update role.');
            } else {
                DB::commit();
                return ResponseHelper::success('Role updated successfully.', NULL, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    
}
