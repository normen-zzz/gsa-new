<?php

namespace Database\Seeders;

use App\Models\master\CustomerModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Contracts\Queue\Job;
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
            AirlineSeeder::class,
            AirportSeeder::class,
            AwbSeeder::class,
            CountrySeeder::class,
            CustomerSeeder::class,
            DivisionSeeder::class,
            FlowapprovalSeeder::class,
            JobSeeder::class,
            JobsheetSeeder::class,
            MenuSeeder::class,
            MenusSuperAdmin::class,
            PermissionSeeder::class,
            PositionSeeder::class,
            RouteSeeder::class,
            SalesorderSeeder::class,
            ShippinginstructionSeeder::class,
            TypecostSeeder::class, // Uncomment if you want to seed typecost
            TypesellingSeeder::class, // Uncomment if you want to seed typeselling
            UserSeeder::class,
            BracketSeeder::class,
            CostSellingSeeder::class

        ]);
        // CREATE JOBS

        DB::table('roles')->insert([
            'name' => 'Super Admin',
            'id_division' => 1, // Assuming IT division has ID 1
            'description' => 'Super Admin Role',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
