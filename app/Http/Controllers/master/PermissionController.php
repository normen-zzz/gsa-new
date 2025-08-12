<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;
use Exception;

date_default_timezone_set('Asia/Jakarta');
class PermissionController extends Controller
{
    public function getPermissions(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'a.id_permission',
            'a.id_position',
            'b.name as position_name',
            'a.id_division',
            'c.name as division_name',
            'a.id_role',
            'd.name as role_name',
            'a.path',
            'a.can_create',
            'a.can_read',
            'a.can_update',
            'a.can_delete',
            'a.can_approve',
            'a.can_reject',
            'a.can_print',
            'a.can_export',
            'a.can_import',
            'a.created_at',
            'a.updated_at',
            'a.status'
        ];

        $permissions = DB::table('permissions AS a')
            ->select($select)
            ->join('positions AS b', 'a.id_position', '=', 'b.id_position')
            ->leftJoin('divisions AS c', 'a.id_division', '=', 'c.id_division')
            ->leftJoin('roles AS d', 'a.id_role', '=', 'd.id_role')
            ->when($search, function ($query) use ($search) {
                $query->where('a.path', 'like', '%' . $search . '%');
                $query->orWhere('a.name', 'like', '%' . $search . '%');
            })
            ->orderBy('a.id_permission', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Permissions retrieved successfully.', $permissions, 200);
    }

    public function getPermissionById($id)
    {
        $select = [
            'a.id_permission',
            'a.id_position',
            'b.name as position_name',
            'a.id_division',
            'c.name as division_name',
            'a.id_role',
            'd.name as role_name',
            'a.path',
            'a.can_create',
            'a.can_read',
            'a.can_update',
            'a.can_delete',
            'a.can_approve',
            'a.can_reject',
            'a.can_print',
            'a.can_export',
            'a.can_import',
            'a.created_at',
            'a.updated_at',
            'a.status'
        ];

        $permission = DB::table('permissions AS a')
            ->select($select)
            ->join('positions AS b', 'a.id_position', '=', 'b.id_position')
            ->leftJoin('divisions AS c', 'a.id_division', '=', 'c.id_division')
            ->leftJoin('roles AS d', 'a.id_role', '=', 'd.id_role')
            ->where('a.id_permission', $id)
            ->first();

        if (!$permission) {
            return ResponseHelper::success('Permission not found.', NULL, 404);
        }

        return ResponseHelper::success('Permission retrieved successfully.', $permission, 200);
    }

    public function createPermission(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_position' => 'required|exists:positions,id_position',
                'id_division' => 'nullable|exists:divisions,id_division',
                'id_role' => 'nullable|exists:roles,id_role',
                'path' => 'required|string|max:255',
                'can_create' => 'required|boolean',
                'can_read' => 'required|boolean',
                'can_update' => 'required|boolean',
                'can_delete' => 'required|boolean',
                'can_approve' => 'required|boolean',
                'can_reject' => 'required|boolean',
                'can_print' => 'required|boolean',
                'can_export' => 'required|boolean',
                'can_import' => 'required|boolean',
                'status' => 'required|boolean'
            ]);

            $permission = DB::table('permissions')->insertGetId([
                'id_position' => $request->input('id_position'),
                'id_division' => $request->input('id_division', null),
                'id_role' => $request->input('id_role', null),
                'path' => $request->input('path'),
                'can_create' => $request->input('can_create', false),
                'can_read' => $request->input('can_read', true),
                'can_update' => $request->input('can_update', false),
                'can_delete' => $request->input('can_delete', false),
                'can_approve' => $request->input('can_approve', false),
                'can_reject' => $request->input('can_reject', false),
                'can_print' => $request->input('can_print', false),
                'can_export' => $request->input('can_export', false),
                'can_import' => $request->input('can_import', false),
                'created_at' => now(),
                'updated_at' => now(),
                'status' => $request->input('status', true),
            ]);
            if ($permission) {
                // code...
                DB::commit();
                return ResponseHelper::success('Permission created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create permission.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
    public function updatePermission(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_permission');
            $permission = DB::table('permissions')->where('id_permission', $id)->first();
            $request->validate([
                'id_permission' => 'required|exists:permissions,id_permission',
                'id_position' => 'required|exists:positions,id_position',
                'id_division' => 'nullable|exists:divisions,id_division',
                'id_role' => 'nullable|exists:roles,id_role',
                'path' => 'required|string|max:255',
                'can_create' => 'required|boolean',
                'can_read' => 'required|boolean',
                'can_update' => 'required|boolean',
                'can_delete' => 'required|boolean',
                'can_approve' => 'required|boolean',
                'can_reject' => 'required|boolean',
                'can_print' => 'required|boolean',
                'can_export' => 'required|boolean',
                'can_import' => 'required|boolean',
                'status' => 'required|boolean'
            ]);

            $updated = DB::table('permissions')
                ->where('id_permission', $id)
                ->update([
                    'id_position' => $request->input('id_position'),
                    'id_division' => $request->input('id_division', null),
                    'id_role' => $request->input('id_role', null),
                    'path' => $request->input('path'),
                    'can_create' => $request->input('can_create', false),
                    'can_read' => $request->input('can_read', true),
                    'can_update' => $request->input('can_update', false),
                    'can_delete' => $request->input('can_delete', false),
                    'can_approve' => $request->input('can_approve', false),
                    'can_reject' => $request->input('can_reject', false),
                    'can_print' => $request->input('can_print', false),
                    'can_export' => $request->input('can_export', false),
                    'can_import' => $request->input('can_import', false),
                    'updated_at' => now(),  
                    'status' => $request->input('status', true)
                ]);
            $changes = [];
            foreach ($request->only(['id_position', 'id_division', 'id_role', 'path', 'can_create', 'can_read', 'can_update', 'can_delete', 'can_approve', 'can_reject', 'can_print', 'can_export', 'can_import', 'status']) as $key => $value) {
                if ($permission->$key !== $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $permission->$key,
                        'new' => $value,
                    ];
                }
            }
            if ($updated) {
                DB::table('log_permission')->insert([
                    'id_permission' => $id,
                    'action' => json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::commit();
                return ResponseHelper::success('Permission updated successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to update permission.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }


}
