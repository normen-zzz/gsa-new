<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AwbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Insert 1 AWB
        $awbId = DB::table('awb')->insertGetId([
            'id_job' => 1,
            'agent' => 1,
            'data_agent' => 1,
            'consignee' => 'PT Contoh Konsignee',
            'airline' => 1,
            'awb' => '123-45678901',
            'etd' => Carbon::now()->addDays(2)->toDateString(),
            'eta' => Carbon::now()->addDays(5)->toDateString(),
            'pol' => 1,
            'pod' => 2,
            'commodity' => 'Electronics',
            'gross_weight' => 500,
            'chargeable_weight' => 480,
            'pieces' => 10,
            'special_instructions' => 'Handle with care',
            'created_by' => 1,
            'status' => 'awb_received_by_ops',
            'updated_by' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert log untuk AWB di atas
        DB::table('log_awb')->insert([
            'id_awb' => $awbId,
            'action' => json_encode([
                'status' => 'awb_received_by_ops',
                'message' => 'AWB berhasil dibuat oleh user 1'
            ]),
            'id_user' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
