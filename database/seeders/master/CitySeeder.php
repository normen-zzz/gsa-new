<?php

namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('city')->insert([
            ['name_city' => 'Jakarta', 'id_country' => 1, 'created_by' => 1],
            ['name_city' => 'Kuala Lumpur', 'id_country' => 2, 'created_by' => 1],
            ['name_city' => 'Miami', 'id_country' => 3, 'created_by' => 1],
        ]);
    }
}
