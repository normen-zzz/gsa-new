<?php

namespace Database\Seeders\master;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = json_decode(file_get_contents(database_path('seeders/data/positions.json')), true)[2]['data'];

        foreach ($positions as $pos) {
            DB::table('positions')->updateOrInsert(
                ['id_position' => $pos['id_position']],
                [
                    'name' => $pos['name'],
                    'description' => $pos['description'],
                    'status' => $pos['status'],
                    'created_at' => $pos['created_at'],
                    'updated_at' => $pos['updated_at'],
                ]
            );
        }
    }
}

// class PositionSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         DB::table('positions')->insert([
//             [
//                 'name' => 'Super Admin',
//                 'description' => 'Super Admin Position',
//                 'status' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Director',
//                 'description' => 'Director Position',
//                 'status' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'General Manager',
//                 'description' => 'General Manager Position',
//                 'status' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Manager',
//                 'description' => 'Manager Position',
//                 'status' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Staff',
//                 'description' => 'Staff Position',
//                 'status' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//         ]);
//     }
// }
