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
            'airline' => 1, // Assuming the ethiopian airline has ID 1
            'etd' => now()->addDays(3),
            'eta' => now()->addDays(5),
            'pol' => 1, // Assuming the Soekarno-Hatta International Airport has ID 1
            'pod' => 2, // Assuming the Miami International Airport has ID 2
            'commodity' => 'Electronics',
            'gross_weight' => 300, // Weight in grams
            'chargeable_weight' => 350, // Assuming a calculated chargeable weight
            'pieces' => 10,
            'dimensions' => json_encode(
                [
                    ['pieces'=> 2.00,'length' => 50.00, 'width' => 30.00, 'height' => 20.00, 'weight' => 100.00],
                    ['pieces'=> 1.00,'length' => 60.00, 'width' => 30.00, 'height' => 20.00, 'weight' => 100.00],
                    ['pieces'=> 1.00,'length' => 70.00, 'width' => 30.00, 'height' => 20.00, 'weight' => 100.00],
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
