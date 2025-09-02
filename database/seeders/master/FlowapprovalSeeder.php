<?php

namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlowapprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // You can seed the flowapproval_salesorder table with initial data here
        // Example:
        $flowapproval = DB::table('flowapproval_salesorder')->insertGetId([

            'request_position' => 1,
            'request_division' => 1,
            'status' => 'active',

            'created_by' => 1, // Assuming user ID 1 is the admin or creator
            'created_at' => now(),

            // Add more entries as needed
        ]);

        DB::table('detailflowapproval_salesorder')->insert([
            [
                'id_flowapproval_salesorder' => $flowapproval,
                'approval_position' => 2,
                'approval_division' => 1,
                'step_no' => 1,
                'status' => 'active',
                'created_by' => 1, // Assuming user ID 1 is the admin or creator
                'created_at' => now(),
            ],
            // Add more entries as needed
        ]);
    }
}
