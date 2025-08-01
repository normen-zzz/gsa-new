<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                "title" => "Master",
                "url" => "#",
                "icon" => "Warehouse",
                "isActive" => false,
                "items" => [
                    [
                        "title" => "Customer",
                        "url" => "/dashboard/master/master-customer"
                    ],
                    [
                        "title" => "Airport",
                        "url" => "/dashboard/master/master-airport"
                    ],
                    [
                        "title" => "Wilayah",
                        "items" => [
                            [
                                "title" => "Country",
                                "url" => "/dashboard/master/master-negara"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "title" => "Shipping Instructions",
                "icon" => "Sailboat",
                "url" => "/dashboard/shipping-instructions",
                "isActive" => false
            ],
            [
                "title" => "Job",
                "icon" => "BriefcaseBusiness",
                "url" => "/dashboard/job",
                "isActive" => false
            ],
            [
                "title" => "Sales Order",
                "url" => "/dashboard/sales-order",
                "icon" => "Wallet",
                "isActive" => false
            ],
            [
                "title" => "Jobsheet",
                "url" => "/dashboard/jobsheet",
                "icon" => "BookText",
                "isActive" => false
            ],
            [
                "title" => "No HAWB",
                "url" => "/dashboard/no-hawb",
                "icon" => "Backpack",
                "isActive" => false
            ],
            [
                "title" => "Role Management",
                "url" => "/dashboard/role-management",
                "icon" => "Siren",
                "isActive" => true
            ]
           
        ];

        $this->insertMenus($menus);
    }

    private function insertMenus(array $menus, $parentId = null)
    {
        foreach ($menus as $menu) {
            $id = DB::table('list_menu')->insertGetId([
                'name' => $menu['title'],
                'icon' => $menu['icon'] ?? null,
                'path' => $menu['url'] ?? null,
                'parent_id' => $parentId,
                'status' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($menu['items'])) {
                $this->insertMenus($menu['items'], $id);
            }
        }
    }
}


//  // Level 1 Menu
//  $dashboardId = DB::table('list_menu')->insertGetId([
//     'name' => 'Dashboard',
//     'icon' => 'fas fa-home',
//     'path' => '/dashboard',
//     'parent_id' => null,
//     'status' => true,
//     'created_by' => 1,
//     'updated_by' => 1,
//     'created_at' => now(),
//     'updated_at' => now(),
// ]);

// $managementId = DB::table('list_menu')->insertGetId([
//     'name' => 'User Management',
//     'icon' => 'fas fa-users',
//     'path' => null,
//     'parent_id' => null,
   
//     'status' => true,
//     'created_by' => 1,
//     'updated_by' => 1,
//     'created_at' => now(),
//     'updated_at' => now(),
// ]);

// // Level 2 Menu
// $userId = DB::table('list_menu')->insertGetId([
//     'name' => 'Users',
//     'icon' => 'fas fa-user',
//     'path' => '/users',
//     'parent_id' => $managementId,
  
//     'status' => true,
//     'created_by' => 1,
//     'updated_by' => 1,
//     'created_at' => now(),
//     'updated_at' => now(),
// ]);

// // Level 3 Menu
// DB::table('list_menu')->insert([
//     'name' => 'Add User',
//     'icon' => 'fas fa-user-plus',
//     'path' => '/users/create',
//     'parent_id' => $userId,
//     'status' => true,
//     'created_by' => 1,
//     'updated_by' => 1,
//     'created_at' => now(),
//     'updated_at' => now(),
// ]);



// DB::table('menu_user')->insert([
//     'id_position' => 1, // Assuming Super Admin has ID 1
//     'id_division' => 1, // Assuming IT division has ID 1
//     'id_listmenu' => $dashboardId,
//     'can_create' => true,
//     'can_read' => true,
//     'can_update' => true,
//     'can_delete' => true,
//     'can_approve' => true,
//     'can_reject' => true,
//     'can_print' => true,
//     'can_export' => true,
//     'can_import' => true,
//     'status' => true,
//     'created_at' => now(),
//     'updated_at' => now(),
// ]);