<?php

namespace Database\Seeders\master;

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
            'initials' => 'CG',
            'name' => 'Cargo',
            'description' => 'Cargo Selling Type',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1
          ],
          [
            'initials' => 'DG',
            'name' => 'Domestic',
            'description' => 'Domestic Selling Type',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1
          ],
          [
            'initials' => 'IG',
            'name' => 'International',
            'description' => 'International Selling Type',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1
          ]
        ]);
    }
}
