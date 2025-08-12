<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('routes')->insert([
            [
                'airline' => 1, // Assuming Ethiopian Airlines has ID 1
                'pol' => 1,  // Assuming the airport has ID 1
                'pod' => 2,  // Assuming the airport has ID 2
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
                'status' => 'active',
            ],
           
        ]);

    }
}
