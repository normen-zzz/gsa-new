<?php

namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeightbracketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('weight_bracket_costs')->insert([
            [
                'min_weight' => 0,
                'created_by' => 1
            ],
            [
                'min_weight' => 45,
                'created_by' => 1
            ],
            [
                'min_weight' => 100,
                'created_by' => 1
            ],

        ]);

        DB::table('weight_bracket_selling')->insert([
            [
                'min_weight' => 0,
                'created_by' => 1
            ],
            [
                'min_weight' => 45,
                'created_by' => 1
            ],
            [
                'min_weight' => 100,
                'created_by' => 1
            ],

        ]);
    }
}
