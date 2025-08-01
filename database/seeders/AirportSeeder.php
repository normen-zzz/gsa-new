<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('airports')->insert([
            'name_airport' => 'Soekarno-Hatta International Airport',
            'code_airport' => 'CGK',
            'id_country' => 1, // Assuming the Indonesia country has ID 1
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ],[
            'name_airport' => 'Miami International Airport',
            'code_airport' => 'MIA',
            'id_country' => 1, // Assuming the Indonesia country has ID 1
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);

       
    }
}
