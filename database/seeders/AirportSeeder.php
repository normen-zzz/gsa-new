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
            [
                'name_airport' => 'Soekarno-Hatta International Airport',
                'code_airport' => 'CGK',
                'id_country' => 1, // Assuming the Indonesia country has ID 1
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            [
                'name_airport' => 'Miami International Airport',
                'code_airport' => 'MIA',
                'id_country' => 1, // Assuming the Indonesia country has ID 1
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            //malaysia
            [
                'name_airport' => 'Kuala Lumpur International Airport',
                'code_airport' => 'KUL',
                'id_country' => 2, // Assuming the Malaysia country has ID 2
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            //ethiopian
            [
                'name_airport' => 'Addis Ababa Bole International Airport',
                'code_airport' => 'ADD',
                'id_country' => 3, // Assuming the Ethiopia country has ID 3
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            //united states
            [
                'name_airport' => 'Los Angeles International Airport',
                'code_airport' => 'LAX',
                'id_country' => 4, // Assuming the United States country has ID 4
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
            //srilankan
            [
                'name_airport' => 'Bandaranaike International Airport',
                'code_airport' => 'CMB',
                'id_country' => 5, // Assuming the Sri Lanka country has ID 4
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
        ]);
    }
}
