<?php

namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BracketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeder untuk weight_bracket_costs
        DB::table('weight_bracket_costs')->insert([
            // [
            //     'id_weight_bracket_cost' => 1,
            //     'min_weight' => 0,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            //     'deleted_at' => null,
            //     'created_by' => 1,
            //     'updated_by' => 1,
            //     'deleted_by' => null,
            //     'status' => 1,
            // ],
            [
                'id_weight_bracket_cost' => 2,
                'min_weight' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
            [
                'id_weight_bracket_cost' => 3,
                'min_weight' => 45,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
             [
                'id_weight_bracket_selling' => 4,
                'min_weight' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
        ]);

        // Seeder untuk weight_bracket_selling
        DB::table('weight_bracket_selling')->insert([
            // [
            //     'id_weight_bracket_selling' => 1,
            //     'min_weight' => 0,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            //     'deleted_at' => null,
            //     'created_by' => 1,
            //     'updated_by' => 1,
            //     'deleted_by' => null,
            //     'status' => 1,
            // ],
            [
                'id_weight_bracket_selling' => 2,
                'min_weight' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
            [
                'id_weight_bracket_selling' => 3,
                'min_weight' => 45,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
             [
                'id_weight_bracket_selling' => 4,
                'min_weight' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
        ]);
    }
}
