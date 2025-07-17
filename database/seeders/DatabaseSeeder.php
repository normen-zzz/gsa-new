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
            'name' => 'Super Admin',
            'description' => 'Super Admin Position',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('positions')->insert([
            'name' => 'Manager',
            'description' => 'Manager Position',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('positions')->insert([
            'name' => 'Staff',
            'description' => 'Staff Position',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('divisions')->insert([
            'name' => 'IT',
            'description' => 'Information Technology Division',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('divisions')->insert([
            'name' => 'Finance',
            'description' => 'Finance Division',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // cs 
        DB::table('divisions')->insert([
            'name' => 'Customer Service',
            'description' => 'Customer Service Division',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('divisions')->insert([
            'name' => 'Account Management',
            'description' => 'Account Management Division',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('roles')->insert([
            'name' => 'Super Admin',
            'description' => 'Super Admin Role',
            'status' => true,
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


        DB::table('customer_details')->insert([
            'email' => 'ceva@logistics.com',
            'phone' => '12345678',
            'address' => '123 CEVA St.',
            'id_customer' => 1,
            'deleted_at' => null,
            'tax_id' => '1234567890',
            'pic' => 'John Doe',
            'created_at' => now(),
            'updated_at' => now(),
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
            'date' => now(),
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
    }
}
