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
       

        $this->call([
            DivisionSeeder::class,
            PositionSeeder::class,
            PermissionSeeder::class,
            AirlineSeeder::class,
            AirportSeeder::class,
            ShippinginstructionSeeder::class,
            CustomerSeeder::class,
            MenuSeeder::class,
        ]);
        // CREATE JOBS

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
            'id_position' => 4,
            'id_division' => 3,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);


        
        

        DB::table('roles')->insert([
            'name' => 'Super Admin',
            'id_division' => 1, // Assuming IT division has ID 1
            'description' => 'Super Admin Role',
            'status' => true,
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
        

       

       

        
    }


}
