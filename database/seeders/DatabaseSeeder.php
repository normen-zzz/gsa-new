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

        DB::table('positions')->insert([
            [
                'name' => 'Super Admin',
                'description' => 'Super Admin Position',
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
            ]
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
            ]
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

        DB::table('list_menu')->insert([
            [
                'name' => 'Dashboard',
                'icon' => 'dashboard',
                'path' => '/dashboard',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Master Customer',
                'icon' => 'people',
                'path' => '/master-customer',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        DB::table('list_childmenu')->insert([
            [
                'id_listmenu' => 2, // Assuming Master Customer has ID 2
                'name' => 'Employee',
                'icon' => 'person',
                'path' => '/master-customer/employee',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_listmenu' => 2, // Assuming Master Customer has ID 2
                'name' => 'Customer',
                'icon' => 'business',
                'path' => '/master-customer/customer',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        DB::table('menu_user')->insert([
            'id_position' => 1, // Assuming Super Admin position has ID 1
            'id_division' => 1, // Assuming IT division has ID 1
            'id_role' => 1, // Assuming Super Admin role has ID 1
            'menu' => json_encode([
                [
                    'menu_id' => 2, // Assuming Master Customer has ID 2
                    'childmenus' => [
                        ['id_childmenu' => 1], // Assuming employee has ID 1
                    ]
                ],
                [
                    'menu_id' => 1,
                    'childmenus' => []
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
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
            'consignee' => 2, // Assuming the DHL customer has ID 2
            'type' => 'direct',

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

        ]);

        DB::table('airlines')->insert([
            'name_airline' => 'Ethiopian Airlines',
            'code_airline' => 'ET',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);
        DB::table('airlines')->insert([
            'name_airline' => 'Srilankan Airlines',
            'code_airline' => 'UL',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ]);
    }


}
