<?php

namespace Database\Seeders\master;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = json_decode(file_get_contents(database_path('seeders/data/divisions.json')), true)[2]['data'];

        foreach ($divisions as $division) {
            DB::table('divisions')->updateOrInsert(
                ['id_division' => $division['id_division']],
                [
                    'name' => $division['name'],
                    'description' => $division['description'],
                    'have_role' => $division['have_role'],
                    'status' => $division['status'],
                    'created_at' => $division['created_at'],
                    'updated_at' => $division['updated_at'],
                ]
            );
        }
    }
}


// class DivisionSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         DB::table('divisions')->insert([
//             [
//                 'name' => 'Super Admin',
//                 'description' => 'Super Admin ',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Director',
//                 'description' => 'Director ',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'General Manager',
//                 'description' => 'General Manager ',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Manager',
//                 'description' => 'Manager ',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Customer Service',
//                 'description' => 'Customer Service Division',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Key Account',
//                 'description' => 'Key Account Management Division',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Sales',
//                 'description' => 'Sales Division',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'OPS',
//                 'description' => 'OPS Division',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'Finance',
//                 'description' => 'Finance Division',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'name' => 'IT',
//                 'description' => 'Information Technology Division',
//                 'status' => true,
//                 'have_role' => true,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//         ]);
//     }
// }
