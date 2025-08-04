<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypesellingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('typeselling')->insert([
            [
                'initials' => 'TS1',
                'name' => 'Type Selling 1',
                'description' => 'Description for Type Selling 1',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'initials' => 'TS2',
                'name' => 'Type Selling 2',
                'description' => 'Description for Type Selling 2',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
