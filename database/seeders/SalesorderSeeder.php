<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesorderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesorderId = DB::table('salesorder')->insertGetId([
            'id_shippinginstruction' => 1,
            'id_job' => 1,
            'id_awb' => 1,
            'remarks' => 'Sales Order pertama untuk testing',
            'created_by' => 1,
            'status' => 'so_created_by_sales',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel attachments_salesorder
        DB::table('attachments_salesorder')->insert([
            'id_salesorder' => $salesorderId,
            'file_name' => 'invoice.pdf',
            'url' => 'https://example.com/invoice.pdf',
            'public_id' => 'attachments/invoice123',
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel selling_salesorder
        DB::table('selling_salesorder')->insert([
            'id_salesorder' => $salesorderId,
            'id_typeselling' => 1,
            'selling_value' => 1500000.00,
            'charge_by' => 'gross_weight',
            'description' => 'Selling berdasarkan gross weight',
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel log_salesorder
        DB::table('log_salesorder')->insert([
            'id_salesorder' => $salesorderId,
            'action' => json_encode([
                'status' => 'so_created_by_sales',
                'message' => 'Sales order dibuat oleh user 1'
            ]),
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel approval_salesorder
        DB::table('approval_salesorder')->insert([
            'id_salesorder' => $salesorderId,
            'approval_position' => 1,
            'approval_division' => 1,
            'step_no' => 1,
            'next_step' => 2,
            'status' => 'pending',
            'remarks' => 'Menunggu persetujuan manager',
            'approved_at' => null,
            'approved_by' => null,
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
