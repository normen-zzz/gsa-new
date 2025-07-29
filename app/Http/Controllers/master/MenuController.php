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

            DB::commit();
            return ResponseHelper::success('Menu updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
