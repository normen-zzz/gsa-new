<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

class MenuController extends Controller
{
    public function getListMenu(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'list_menu.id_listmenu',
            'list_menu.name',
            'list_menu.icon',
            'list_menu.path',
            'list_menu.status',
            'list_menu.created_at',
            'list_menu.updated_at',
        ];

        $menus = DB::table('list_menu')
            ->select($select)

            ->when($search, function ($query) use ($search) {
                return $query->where('list_menu.name', 'like', '%' . $search . '%');
            })
            ->orderBy('list_menu.id_listmenu', 'asc')
            ->paginate($limit);

        //    insert child_menu to $menus 
        foreach ($menus as $menu) {
            $childMenus = DB::table('list_childmenu')
                ->where('id_listmenu', $menu->id_listmenu)
                ->get(['id_listchildmenu', 'name', 'icon', 'path', 'status']);
            if ($childMenus->isEmpty()) {
                $menu->child_menus = [];
            } else {
                $menu->child_menus = $childMenus;
            }
        }

        //    end insert child_menu to $menus

        return ResponseHelper::success('Menus retrieved successfully.', $menus, 200);
    }

    public function getListMenuById($id)
    {
        $menu = DB::table('list_menu')
            ->where('id_listmenu', $id)
            ->first();

        if (!$menu) {
           return ResponseHelper::success('Menu not found.', NULL, 404);
        }

        return ResponseHelper::success('Menu retrieved successfully.', $menu, 200);
    }

    public function createListMenu(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:list_menu,name',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255|unique:list_menu,path',
                'status' => 'required|boolean|default:true',
            ]);

            DB::table('list_menu')->insert([
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'path' => $request->input('path'),
                'status' => $request->input('status'),
                'created_at' => now(),
                'updated_at' => now()
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
            $request->validate([
                'name' => 'required|string|max:255|unique:list_menu,name,' . $id . ',id_listmenu',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255|unique:list_menu,path,' . $id . ',id_listmenu',
                'status' => 'required|boolean',
            ]);

            $menu = DB::table('list_menu')
                ->where('id_listmenu', $id)
                ->update([
                    'name' => $request->input('name'),
                    'icon' => $request->input('icon'),
                    'path' => $request->input('path'),
                    'status' => $request->input('status', true),
                    'updated_at' => now(),
                ]);

            if (!$menu) {
                throw new Exception('Failed to update menu.');
            }

            DB::commit();

            return ResponseHelper::success('Menu updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getListChildMenu(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'child_menu.id_childmenu',
            'list_menu.id_listmenu',
            'list_menu.name as list_menu_name',
            'list_menu.icon as list_menu_icon',
            'list_menu.path as list_menu_path',
            'child_menu.name',
            'child_menu.icon',
            'child_menu.path',
            'child_menu.status',
            'child_menu.created_at',
            'child_menu.updated_at',
        ];

        $menus = DB::table('child_menu')
            ->select($select)
            ->join('list_menu', 'child_menu.id_listmenu', '=', 'list_menu.id_listmenu')
            ->when($search, function ($query) use ($search) {
                return $query->where('child_menu.name', 'like', '%' . $search . '%');
            })
            ->orderBy('child_menu.id_childmenu', 'desc')
            ->paginate($limit);
        return ResponseHelper::success('Child menus retrieved successfully.', $menus, 200);
    }
    public function getListChildMenuById($id)
    {
        $select = [
            'child_menu.id_childmenu',
            'list_menu.id_listmenu',
            'list_menu.name as list_menu_name',
            'list_menu.icon as list_menu_icon',
            'list_menu.path as list_menu_path',
            'child_menu.name',
            'child_menu.icon',
            'child_menu.path',
            'child_menu.status',
            'child_menu.created_at',
            'child_menu.updated_at',
        ];
        $menu = DB::table('child_menu')
            ->select($select)
            ->join('list_menu', 'child_menu.id_listmenu', '=', 'list_menu.id_listmenu')
            ->where('id_childmenu', $id)
            ->first();

        if (!$menu) {
           return ResponseHelper::success('Child menu not found.', NULL, 404);
        }

        return ResponseHelper::success('Child menu retrieved successfully.', $menu, 200);
    }

    public function createListChildMenu(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_listmenu' => 'required|exists:list_menu,id_listmenu',
                'name' => 'required|string|max:255|unique:list_childmenu,name',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255|unique:list_childmenu,path',
                'status' => 'required|boolean',
            ]);

            $menu = DB::table('list_childmenu')->insert([
                'id_listmenu' => $request->input('id_listmenu'),
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'path' => $request->input('path'),
                'status' => $request->input('status', true),
            ]);

            DB::commit();

            return ResponseHelper::success('Child menu created successfully.', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateListChildMenu(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_listchildmenu');
            $request->validate([
                'id_listchildmenu' => 'required|integer|exists:list_childmenu,id_listchildmenu',
                'id_listmenu' => 'required|exists:list_menu,id_listmenu',
                'name' => 'required|string|max:255|unique:list_childmenu,name,' . $id . ',id_listchildmenu',
                'icon' => 'nullable|string|max:255',
                'path' => 'required|string|max:255|unique:list_childmenu,path,' . $id . ',id_listchildmenu',
                'status' => 'required|boolean',
            ]);

            $menu = DB::table('list_childmenu')
                ->where('id_listchildmenu', $id)
                ->update([
                    'id_listmenu' => $request->input('id_listmenu'),
                    'name' => $request->input('name'),
                    'icon' => $request->input('icon'),
                    'path' => $request->input('path'),
                    'status' => $request->input('status') ?? true,
                    'updated_at' => now(),
                ]);

            if (!$menu) {
                throw new Exception('Failed to update child menu.');
            }

            DB::commit();

            return ResponseHelper::success('Child menu updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
