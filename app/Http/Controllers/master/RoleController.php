<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

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

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $roles,
            'meta_data' => [
                'code' => 200,
                'message' => 'Roles retrieved successfully.',
            ]
        ], 200);
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
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Role not found.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $role,
            'meta_data' => [
                'code' => 200,
                'message' => 'Role retrieved successfully.',
            ]
        ], 200);
    }
    public function createRole(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_division' => 'required|exists:divisions,id_division',
                'name' => 'required|string|max:255|unique:roles,name',
                'description' => 'nullable|string|max:500',
                'status' => 'boolean'
            ]);

            $role = DB::table('roles')->insertGetId([
                'id_division' => $request->input('id_division'),
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'status' => $request->input('status', true),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'code' => 201,
                'status' => 'success',
                'data' => ['id_role' => $role],
                'meta_data' => [
                    'code' => 201,
                    'message' => 'Role created successfully.',
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'meta_data' => [
                        'code' => 200,
                        'message' => 'Role updated successfully.',
                    ]
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    
}
