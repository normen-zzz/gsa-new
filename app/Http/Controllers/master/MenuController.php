<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

date_default_timezone_set('Asia/Jakarta');

class MenuController extends Controller
{
    public function getListMenu(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $listMenu = DB::table('list_menu')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('path', 'like', '%' . $search . '%');
            })
            ->orderBy('id_listmenu', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('List of menus retrieved successfully.', $listMenu, 200);
    }

    public function getListMenuById($id)
    {
        $menu = DB::table('list_menu')
            ->where('id_listmenu', $id)
            ->first();

        if (!$menu) {
            return ResponseHelper::success('Menu not found.', NULL, 404);
        } else {
            return ResponseHelper::success('Menu retrieved successfully.', $menu, 200);
        }
    }

    public function createListMenu(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:list_menu,name',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255|unique:list_menu,path',
                'parent_id' => 'nullable|integer|exists:list_menu,id_listmenu',
                'status' => 'required|boolean',
            ]);

            DB::table('list_menu')->insert([
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'path' => $request->input('path'),
                'parent_id' => $request->input('parent_id', NULL),

                'status' => $request->input('status', true),
                'created_by' => $request->user()->id_user,
                'updated_by' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Menu created successfully.', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateListMenu(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_listmenu');
            $list_menu = DB::table('list_menu')->where('id_listmenu', $id)->first();
            $request->validate([
                'id_listmenu' => 'required|integer|exists:list_menu,id_listmenu',
                'name' => 'required|string|max:255|unique:list_menu,name,' . $id . ',id_listmenu',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255|unique:list_menu,path,' . $id . ',id_listmenu',
                'parent_id' => 'nullable|integer|exists:list_menu,id_listmenu',

                'status' => 'required|boolean',
            ]);

            DB::table('list_menu')
                ->where('id_listmenu', $id)
                ->update([
                    'name' => $request->input('name'),
                    'icon' => $request->input('icon'),
                    'path' => $request->input('path'),
                    'parent_id' => $request->input('parent_id', NULL),
                    'status' => $request->input('status', true),
                    'updated_by' => $request->user()->id_user,
                    'updated_at' => now(),
                ]);
                $changes = [];
            foreach ($request->only(['name', 'icon', 'path', 'parent_id', 'status']) as $key => $value) {
                if ($list_menu->$key !== $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $list_menu->$key,
                        'new' => $value,
                    ];
                }
            }
            DB::table('log_listmenu')->insert([
                'id_listmenu' => $id,
                'action' => json_encode($changes),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Menu updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getMenuUser(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $id_position = $request->input('id_position', null);
        $id_division = $request->input('id_division', null);


        $listMenu = DB::table('menu_user AS a')
        ->join('positions AS b', 'a.id_position', '=', 'b.id_position')
        ->join('divisions AS c', 'a.id_division', '=', 'c.id_division')
        ->join('list_menu AS d', 'a.id_listmenu', '=', 'd.id_listmenu')
        ->select(
            'a.id_menu_user',
            'a.id_position',
            'b.name AS position_name',
            'a.id_division',
            'c.name AS division_name',
            'a.id_listmenu',
            'd.name AS menu_name',
            'd.icon',
            'd.path',
            'a.status',
            'a.created_at',
            'a.updated_at',
            'a.can_create',
            'a.can_read',
            'a.can_update',
            'a.can_delete',
            'a.can_approve',
            'a.can_reject',
            'a.can_print',
            'a.can_export',
            'a.can_import'
        )
            ->when($id_position, function ($query) use ($id_position) {
                return $query->where('a.id_position', $id_position);
            })
            ->when($id_division, function ($query) use ($id_division) {
                return $query->where('a.id_division', $id_division);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('path', 'like', '%' . $search . '%');
            })
            ->orderBy('id_menu_user', 'asc')
            ->paginate($limit);


        return ResponseHelper::success('List of user menus retrieved successfully.', $listMenu, 200);
    }
    
    public function getMenuUserById(Request $request)
    {
        $id_menu_user = $request->input('id');
        $menuUser = DB::table('menu_user AS a')
            ->join('positions AS b', 'a.id_position', '=', 'b.id_position')
            ->join('divisions AS c', 'a.id_division', '=', 'c.id_division')
            ->join('list_menu AS d', 'a.id_listmenu', '=', 'd.id_listmenu')
            ->select(
                'a.id_menu_user',
                'a.id_position',
                'b.name AS position_name',
                'a.id_division',
                'c.name AS division_name',
                'a.id_listmenu',
                'd.name AS menu_name',
                'd.icon',
                'd.path',
                'a.status',
                'a.created_at',
                'a.updated_at',
                'a.can_create',
                'a.can_read',
                'a.can_update',
                'a.can_delete',
                'a.can_approve',
                'a.can_reject',
                'a.can_print',
                'a.can_export',
                'a.can_import'
            )
            ->where('id_menu_user', $id_menu_user)
            ->first();
        if (!$menuUser) {
            return ResponseHelper::success('Menu user not found.', NULL, 404);
        }else{
            return ResponseHelper::success('Menu user retrieved successfully.', $menuUser, 200);
        }

    }

    public function createMenuUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_position' => 'required|exists:positions,id_position',
                'id_division' => 'required|exists:divisions,id_division',
                'id_listmenu' => 'required|exists:list_menu,id_listmenu',
                'status' => 'required|boolean',
                'can_create' => 'boolean',
                'can_read' => 'boolean',
                'can_update' => 'boolean',
                'can_delete' => 'boolean',
                'can_approve' => 'boolean',
                'can_reject' => 'boolean',
                'can_print' => 'boolean',
                'can_export' => 'boolean',
                'can_import' => 'boolean'
            ]);

            DB::table('menu_user')->insert([
                'id_position' => $request->input('id_position'),
                'id_division' => $request->input('id_division'),
                'id_listmenu' => $request->input('id_listmenu'),
                'status' => $request->input('status', true),
                'can_create' => $request->input('can_create', false),
                'can_read' => $request->input('can_read', false),
                'can_update' => $request->input('can_update', false),
                'can_delete' => $request->input('can_delete', false),
                'can_approve' => $request->input('can_approve', false),
                'can_reject' => $request->input('can_reject', false),
                'can_print' => $request->input('can_print', false),
                'can_export' => $request->input('can_export', false),
                'can_import' => $request->input('can_import', false),
                'created_by' => $request->user()->id_user,
                'updated_by' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return ResponseHelper::success('Menu user created successfully.', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateMenuUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_menu_user');
            $menuUser = DB::table('menu_user')->where('id_menu_user', $id)->first();
            $request->validate([
                'id_menu_user' => 'required|exists:menu_user,id_menu_user',
                'id_position' => 'required|exists:positions,id_position',
                'id_division' => 'required|exists:divisions,id_division',
                'id_listmenu' => 'required|exists:list_menu,id_listmenu',
                'status' => 'required|boolean',
                'can_create' => 'boolean',
                'can_read' => 'boolean',
                'can_update' => 'boolean',
                'can_delete' => 'boolean',
                'can_approve' => 'boolean',
                'can_reject' => 'boolean',
                'can_print' => 'boolean',
                'can_export' => 'boolean',
                'can_import' => 'boolean'
            ]);

            DB::table('menu_user')
                ->where('id_menu_user', $id)
                ->update([
                    'id_position' => $request->input('id_position'),
                    'id_division' => $request->input('id_division'),
                    'id_listmenu' => $request->input('id_listmenu'),
                    'status' => $request->input('status', true),
                    'can_create' => $request->input('can_create', false),
                    'can_read' => $request->input('can_read', false),
                    'can_update' => $request->input('can_update', false),
                    'can_delete' => $request->input('can_delete', false),
                    'can_approve' => $request->input('can_approve', false),
                    'can_reject' => $request->input('can_reject', false),
                    'can_print' => $request->input('can_print', false),
                    'can_export' => $request->input('can_export', false),
                    'can_import' => $request->input('can_import', false),
                    'updated_by' => $request->user()->id_user,
                    'updated_at' => now(),
                ]);
            $changes = [];
            foreach ($request->only(['id_position', 'id_division', 'id_listmenu', 'status', 'can_create', 'can_read', 'can_update', 'can_delete', 'can_approve', 'can_reject', 'can_print', 'can_export', 'can_import']) as $key => $value) {
                if ($menuUser->$key !== $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $menuUser->$key,
                        'new' => $value,
                    ];
                }
            }
            if (!empty($changes)) {
                DB::table('log_menu_user')->insert([
                    'id_menu_user' => $id,
                    'action' => json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return ResponseHelper::success('Menu user updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
