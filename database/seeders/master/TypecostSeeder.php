<?php

namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TypecostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('typecost')->insert([
            [
                'initials' => 'FRT',
                'name' => 'Air Freight',
                'description' => 'Cost associated with air freight services',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            [
                'initials' => 'GHD',    
                'name' => 'Ground Handling',
                'description' => 'Cost for ground handling services',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            [
                'initials' => 'CUS',
                'name' => 'Customs Clearance',
                'description' => 'Cost for customs clearance services',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
        ]);
    }
}
