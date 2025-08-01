<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            [
                'id_position' => 1,
                'id_division' => 1,
                'path' => '/master-customer',
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_approve' => true,
                'can_reject' => true,
                'can_print' => true,
                'can_export' => true,
                'can_import' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_position' => 2,
                'id_division' => 2,
                'path' => '/master-customer',
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_approve' => true,
                'can_reject' => true,
                'can_print' => true,
                'can_export' => true,
                'can_import' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
