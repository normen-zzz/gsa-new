<?php

namespace Database\Seeders;

use App\Models\master\CustomerModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Claims\Custom;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CREATE MENUS 
        $this->call(MenuSeeder::class);

        // Create a default user
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@transtama.com',
            'password' => bcrypt('admin123'),
            'id_position' => 1,
            'id_division' => 1,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);

        User::factory()->create([
            'name' => 'sales',
            'email' => 'sales@transtama.com',
            'password' => bcrypt('admin123'),
            'id_position' => 4,
            'id_division' => 5,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);

        User::factory()->create([
            'name' => 'cs',
            'email' => 'cs@transtama.com',
            'password' => bcrypt('admin123'),
            'id_position' => 5,
            'id_division' => 3,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);


        DB::table('positions')->insert([
            [
                'name' => 'Super Admin',
                'description' => 'Super Admin Position',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Director',
                'description' => 'Director Position',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General Manager',
                'description' => 'General Manager Position',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'description' => 'Manager Position',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Staff',
                'description' => 'Staff Position',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('divisions')->insert([
            [
                'name' => 'IT',
                'description' => 'Information Technology Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance',
                'description' => 'Finance Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Customer Service Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Account Management',
                'description' => 'Account Management Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales Division',
                'status' => true,
                'have_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('roles')->insert([
            'name' => 'Super Admin',
            'id_division' => 1, // Assuming IT division has ID 1
            'description' => 'Super Admin Role',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('permissions')->insert([
            [
                'id_position' => 1,
                'id_division' => 1,
                'path' => '/master-customer',
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_approve' => true,
                'can_reject' => true,
                'can_print' => true,
                'can_export' => true,
                'can_import' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_position' => 2,
                'id_division' => 2,
                'path' => '/master-customer',
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_approve' => true,
                'can_reject' => true,
                'can_print' => true,
                'can_export' => true,
                'can_import' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        DB::table('customers')->insert([
            'name_customer' => 'CEVA',
            'type' => 'agent',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1

        ]);

        DB::table('customers')->insert([
            'name_customer' => 'DHL',
            'type' => 'consignee',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1

        ]);

        DB::table('data_customer')->insert([
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
        ]);

        DB::table('data_customer')->insert([
            'id_customer' => 2, // Assuming the DHL customer has ID 2
            'data' => json_encode(
                [
                    'pic' => 'Jane Smith',
                    'email' => 'jane.smith@dhl.com',
                    'address' => '456 DHL Avenue',
                    'phone' => '987654321',
                    'tax_id' => '0987654321',
                ]
            ),
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);

        DB::table('countries')->insert([
            'name_country' => 'Indonesia',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);
        DB::table('airports')->insert([
            'name_airport' => 'Soekarno-Hatta International Airport',
            'code_airport' => 'CGK',
            'id_country' => 1, // Assuming the Indonesia country has ID 1
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);

        DB::table('airports')->insert([
            'name_airport' => 'Miami International Airport',
            'code_airport' => 'MIA',
            'id_country' => 1, // Assuming the Indonesia country has ID 1
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);

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
            'weight' => 1000, // Weight in grams
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
            'status' => 'created_by_sales',
            'created_at' => now(),
            'updated_at' => now(),
            'received_at' => null,
            'received_by' => null,

        ]);

        DB::table('airlines')->insert([
            'name' => 'Ethiopian Airlines',
            'code' => 'ET',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);
        DB::table('airlines')->insert([
            'name' => 'Srilankan Airlines',
            'code' => 'UL',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);

        
    }


}
