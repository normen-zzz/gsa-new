<?php

namespace Database\Seeders\master;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert(
            [
                'name' => 'Super Admin',
                'id_division' => 1, // Assuming IT division has ID 1
                'description' => 'Super Admin Role',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Director',
                'id_division' => 2, 
                'description' => 'Director',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
    );
    }
}