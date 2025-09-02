<?php

namespace Database\Seeders\master;
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
                        "title" => "Airlines",
                        "url" => "/dashboard/master/master-airlines"
                    ],
                    [
                        "title" => "Airport",
                        "url" => "/dashboard/master/master-airport"
                    ],
                    [
                        "title" => "Customer",
                        "url" => "/dashboard/master/master-customer"
                    ],
                    [
                        "title" => "Company",
                        "url" => "/dashboard/master/master-company"
                    ],
                    [
                        "title" => "Vendor",
                        "url" => "/dashboard/master/master-vendor"
                    ],
                    [
                        "title" => "Routes",
                        "url" => "/dashboard/master/master-routes"
                    ],
                    [
                        "title" => "Weight Brackets",
                        "items" => [
                            [
                                "title" => "Bracket Cost",
                                "url" => "/dashboard/master/weight-brackets/weight-brackets-cost"
                            ],
                            [
                                "title" => "Bracket Selling",
                                "url" => "/dashboard/master/weight-brackets/weight-brackets-selling"
                            ]
                        ]
                    ],
                    [
                        "title" => "Cost",
                        "items" => [
                            [
                                "title" => "Cost Type",
                                "url" => "/dashboard/master/master-cost/master-cost-type"
                            ],
                            [
                                "title" => "List Cost",
                                "url" => "/dashboard/master/master-cost/master-list-cost"
                            ]
                        ]
                    ],
                    [
                        "title" => "Selling",
                        "items" => [
                            [
                                "title" => "Selling Type",
                                "url" => "/dashboard/master/master-selling/master-selling-type"
                            ],
                            [
                                "title" => "List Selling",
                                "url" => "/dashboard/master/master-selling/master-selling"
                            ]
                        ]
                    ],
                    [
                        "title" => "Other Charge",
                        "url" => "/dashboard/master/other-charge"
                    ],
                    [
                        "title" => "Wilayah",
                        "items" => [
                            [
                                "title" => "Country",
                                "url" => "/dashboard/master/master-negara"
                            ]
                            ]
                        ],
                        [
                            "title" => "Approval Flow",
                            "items" => [
                                [
                                    "title" => "Sales Order Flow",
                                    "url" => "/dashboard/master/master-approval/sales-order"
                                ],
                                [
                                    "title" => "Jobsheet Flow",
                                    "url" => "/dashboard/master/master-approval/jobsheet"
                                ],
                                [
                                    "title" => "Invoice Flow",
                                    "url" => "/dashboard/master/master-approval/invoice"
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
                "url" => "#",
                "isActive" => false,
                "items" => [
                    [
                        "title" => "Job List",
                        "url" => "/dashboard/job/job-list",
                    ],
                    [
                        "title" => "Execute Job",
                        "url" => "/dashboard/job/execute-job",
                    ],
                ]
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
            // [
            //     "title" => "No HAWB",
            //     "url" => "/dashboard/no-hawb",
            //     "icon" => "Backpack",
            //     "isActive" => false
            // ],
            [
                "title" => "Invoice",
                "url" => "/dashboard/invoices",
                "icon" => "Receipt",
                "isActive" => true
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