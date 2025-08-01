<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenusSuperAdmin extends Seeder
{
    public function run(): void
    {
        // Ambil semua id dari list_menu
        $menuIds = DB::table('list_menu')->pluck('id_listmenu');

        foreach ($menuIds as $menuId) {
            DB::table('menu_user')->insert([
                'id_position' => 1, 
                'id_division' => 1, 
                'id_listmenu' => $menuId,
                'status' => true,
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_approve' => true,
                'can_reject' => true,
                'can_print' => true,
                'can_export' => true,
                'can_import' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
