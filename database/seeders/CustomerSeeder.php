<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert(
            [
                'name_customer' => 'CEVA',
                'type' => 'agent',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1

            ],
            [
                'name_customer' => 'TCS',
                'type' => 'agent',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1

            ],
            [
                'name_customer' => 'PT. Transtama',
                'type' => 'agent',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1

            ],
            [
                'name_customer' => 'DHL',
                'type' => 'consignee',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1

            ],
            [
                'name_customer' => 'FedEx',
                'type' => 'consignee',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1

            ]
        );

        DB::table('data_customer')->insert(
            [
                'id_customer' => 1, // Assuming the CEVA customer has ID 1
                'data' => json_encode(
                    [
                        'pic' => 'John Doe',
                        'email' => 'john.doe@ceva.com',
                        'address' => '123 CEVA Street',
                        'phone' => '123456789',
                        'tax_id' => '1234567890',
                    ]
                ),
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1, // Assuming the admin user has ID 1
            ],
           
        );
    }
}
