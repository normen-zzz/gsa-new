<?php

namespace Database\Seeders;

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
        DB::table('flowapproval_salesorder')->insert([
            [
                'request_position' => 1,
                'request_division' => 1,
                'approval_position' => 2,
                'approval_division' => 1,
                'step_no' => 1,
                'status' => 'active',
                'next_step' => null,
                'created_by' => 1, // Assuming user ID 1 is the admin or creator
                'created_at' => now(),
            ],
            // Add more entries as needed
        ]);
    }
}
