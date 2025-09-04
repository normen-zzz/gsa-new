<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AwbSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\JobsheetSeeder;
use Database\Seeders\master\JobSeeder;
use Database\Seeders\SalesorderSeeder;
use Database\Seeders\master\CostSellingSeeder;
use Database\Seeders\master\MenuSeeder;
use Database\Seeders\master\UserSeeder;
use Database\Seeders\master\RouteSeeder;
use Database\Seeders\master\AirlineSeeder;
use Database\Seeders\master\AirportSeeder;
use Database\Seeders\master\BracketSeeder;
use Database\Seeders\master\CountrySeeder;
use Database\Seeders\master\CustomerSeeder;
use Database\Seeders\master\DivisionSeeder;
use Database\Seeders\master\PositionSeeder;
use Database\Seeders\master\TypecostSeeder;
use Database\Seeders\master\MenusSuperAdmin;
use Database\Seeders\master\PermissionSeeder;
use Database\Seeders\master\TypesellingSeeder;
use Database\Seeders\master\FlowapprovalSeeder;
use Database\Seeders\master\RoleSeeder;
use Database\Seeders\master\UserMenuAndPermissionSeeder;
use Database\Seeders\ShippinginstructionSeeder;

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
            // FlowapprovalSeeder::class,
            // JobSeeder::class,
            // JobsheetSeeder::class,
            PositionSeeder::class,
            DivisionSeeder::class,
            PermissionSeeder::class,
            MenuSeeder::class,
            // MenusSuperAdmin::class,
            RouteSeeder::class,
            SalesorderSeeder::class,
            // ShippinginstructionSeeder::class,
            TypecostSeeder::class,
            TypesellingSeeder::class, 
            BracketSeeder::class,
            CostSellingSeeder::class,
            RoleSeeder::class,
            UserMenuAndPermissionSeeder::class,
            UserSeeder::class,
        ]);

    }
}
