<?php

namespace Database\Seeders\master;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostSellingSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder untuk tabel cost
        DB::table('cost')->insert([
            [
                'id_cost' => 1,
                'id_weight_bracket_cost' => 1,
                'id_typecost' => 1,
                'id_route' => 1,
                'cost_value' => 100,
                'charge_by' => 'awb',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'status' => 1,
            ],
        ]);

        // Seeder untuk tabel selling
        DB::table('selling')->insert([
            [
                'id_selling' => 1,
                'id_weight_bracket_selling' => 1,
                'id_typeselling' => 1,
                'id_route' => 1,
                'selling_value' => 200000,
                'charge_by' => 'awb',
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
