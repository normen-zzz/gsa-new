<?php

namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use illuminate\Support\Facades\DB;


class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert ke tabel job
        $jobId = DB::table('job')->insertGetId([
            'id_shippinginstruction' => 1,
            'awb' => '123-45678901',
            'agent' => 1,
            'data_agent' => 1,
            'consignee' => 'PT Contoh Consignee',
            'airline' => 1,
            'etd' => now()->addDays(3),
            'eta' => now()->addDays(7),
            'pol' => 1,
            'pod' => 2,
            'commodity' => 'General Cargo',
            'gross_weight' => 100.50,
            'chargeable_weight' => 98.75,
            'pieces' => 10,
            'special_instructions' => 'Handle with care',
            'created_by' => 1,
            'updated_by' => null,
            'deleted_by' => null,
            'status' => 'job_created_by_cs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert ke tabel log_job
        DB::table('log_job')->insert([
            'id_job' => $jobId,
            'action' => json_encode([
                'message' => 'Job created by CS',
                'awb' => '123-45678901'
            ]),
            'id_user' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
