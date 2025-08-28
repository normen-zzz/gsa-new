<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JobsheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert ke tabel jobsheet
        $jobsheetId = DB::table('jobsheet')->insertGetId([
            'id_shippinginstruction' => 1,
            'id_job' => 1,
            'id_awb' => 1,
            'id_salesorder' => 1,
            'remarks' => 'Jobsheet pertama untuk testing',
            'created_by' => 1,
            'status' => 'js_created_by_cs',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel attachments_jobsheet
        DB::table('attachments_jobsheet')->insert([
            'id_jobsheet' => $jobsheetId,
            'file_name' => 'document.pdf',
            'url' => 'https://example.com/document.pdf',
            'public_id' => 'attachments_jobsheet/doc123',
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel cost_jobsheet
        DB::table('cost_jobsheet')->insert([
            'id_jobsheet' => $jobsheetId,
            'id_typecost' => 1,
            'cost_value' => 250.75,
            'charge_by' => 'chargeable_weight',
            'description' => 'Biaya handling berdasarkan chargeable weight',
            'id_vendor' => 1,
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel log_jobsheet
        DB::table('log_jobsheet')->insert([
            'id_jobsheet' => $jobsheetId,
            'action' => json_encode([
                'status' => 'js_created_by_cs',
                'message' => 'Jobsheet dibuat oleh user 1'
            ]),
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert ke tabel approval_jobsheet
        DB::table('approval_jobsheet')->insert([
            'id_jobsheet' => $jobsheetId,
            'approval_position' => 1,
            'approval_division' => 1,
            'step_no' => 1,
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
