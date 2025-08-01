<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippinginstructionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('shippinginstruction')->insert([
            'agent' => 1, // Assuming the CEVA customer has ID 1
            'data_agent' => 1, // Assuming the CEVA customer data has ID 1
            'consignee' => "tes consignee", // Assuming the DHL customer has ID 2
            'type' => 'direct',
            'etd' => now()->addDays(3),
            'eta' => now()->addDays(5),
            'pol' => 1, // Assuming the Soekarno-Hatta International Airport has ID 1
            'pod' => 2, // Assuming the Miami International Airport has ID 2
            'commodity' => 'Electronics',
            'gross_weight' => 1000, // Weight in grams
            'chargeable_weight' => 1200, // Chargeable weight in grams
            'pieces' => 10,
            'dimensions' => json_encode(
                [
                    ['length' => 50, 'width' => 30, 'height' => 20, 'weight' => 100],
                    ['length' => 60, 'width' => 30, 'height' => 20, 'weight' => 100],
                    ['length' => 70, 'width' => 30, 'height' => 20, 'weight' => 100],
                ]
            ),
            'special_instructions' => 'Handle with care',
            'created_by' => 1, // Assuming the admin user has ID 1
            'status' => 'si_created_by_sales',
            'created_at' => now(),
            'updated_at' => now(),
            'received_at' => null,
            'received_by' => null,

        ]);
    }
}
