<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('divisions')->insert([
            [
                'name' => 'Director',
                'description' => 'Director ',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General Manager',
                'description' => 'General Manager ',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IT',
                'description' => 'Information Technology Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance',
                'description' => 'Finance Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Customer Service Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Account Management',
                'description' => 'Account Management Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Key Account',
                'description' => 'Key Account Management Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
