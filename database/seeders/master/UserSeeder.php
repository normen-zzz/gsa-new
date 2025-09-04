<?php
namespace Database\Seeders\master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * === 1. Pastikan ada Division & Position Super Admin ===
         */
        $division = DB::table('divisions')->where('name', 'Super Admin')->first();
        if (!$division) {
            $divisionId = DB::table('divisions')->insertGetId([
                'name' => 'Super Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $divisionId = $division->id_division;
        }

        $position = DB::table('positions')->where('name', 'Super Admin')->first();
        if (!$position) {
            $positionId = DB::table('positions')->insertGetId([
                'name' => 'Super Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $positionId = $position->id_position;
        }

        /**
         * === 2. Buat User Super Admin ===
         */
        DB::table('users')->updateOrInsert(
            [
                'email' => 'superadmin@transtama.com',
            ],
            [
                'name' => 'superadmin',
                'id_position' => $positionId,
                'id_division' => $divisionId,
                'id_role' => 1, // role super admin
                'password' => Hash::make('superadmin123'),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        echo "✅ Super Admin created: superadmin@transtama.com / superadmin123\n";

        /**
         * === 3. Generate User dari CSV ===
         */
        $csv = Reader::createFromPath(database_path('seeders/data/role_management_final.csv'), 'r');
        $csv->setHeaderOffset(0);

        $users = [];

        foreach ($csv as $record) {
            $division = DB::table('divisions')->where('name', trim($record['division']))->first();
            $position = DB::table('positions')->where('name', trim($record['position']))->first();

            if ($division && $position) {
                $username = strtolower(
                    preg_replace('/\s+/', '', $division->name . $position->name)
                );

                $email = $username . '@transtama.com';
                $password = $username . '123';

                if (!isset($users[$email])) {
                    DB::table('users')->updateOrInsert(
                        [
                            'email' => $email,
                        ],
                        [
                            'name' => $username,
                            'id_position' => $position->id_position,
                            'id_division' => $division->id_division,
                            'id_role' => 2, // role default user
                            'password' => Hash::make($password),
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $users[$email] = true;
                    echo "✅ User created: {$email} / {$password}\n";
                }
            } else {
                echo "⚠️ Division or Position not found for CSV row: " . json_encode($record) . "\n";
            }
        }
    }
}



// class UserSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $csv = Reader::createFromPath(database_path('seeders/data/role_management_final.csv'), 'r');
//         $csv->setHeaderOffset(0);

//         $users = [];

//         foreach ($csv as $record) {
//             $division = DB::table('divisions')->where('name', trim($record['division']))->first();
//             $position = DB::table('positions')->where('name', trim($record['position']))->first();

//             if ($division && $position) {
//                 $username = strtolower(
//                     preg_replace('/\s+/', '', $division->name . $position->name)
//                 );

//                 $email = $username . '@transtama.com';
//                 $password = $username . '123';

//                 // Avoid duplicate user creation
//                 if (!isset($users[$email])) {
//                     DB::table('users')->updateOrInsert(
//                         [
//                             'email' => $email,
//                         ],
//                         [
//                             'name' => $username,
//                             'id_position' => $position->id_position,
//                             'id_division' => $division->id_division,
//                             'id_role' => 1,
//                             'password' => Hash::make($password),
//                             'status' => 1,
//                             'created_at' => now(),
//                             'updated_at' => now(),
//                         ]
//                     );

//                     $users[$email] = true;
//                     echo "✅ User created: {$email} / {$password}\n";
//                 }
//             } else {
//                 echo "⚠️ Division or Position not found for CSV row: " . json_encode($record) . "\n";
//             }
//         }
//     }
// }


// class UserSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         // Create a default user
//         User::factory()->create([
//             'name' => 'admin',
//             'email' => 'admin@transtama.com',
//             'password' => bcrypt('admin123'),
//             'id_position' => 1,
//             'id_division' => 1,
//             'id_role' => 1,
//             'photo' => null,
//             'phone' => '12345678',
//             'status' => true
//         ]);

//         User::factory()->create([
//             'name' => 'Director',
//             'email' => 'director@transtama.com',
//             'password' => bcrypt('director123'),
//             'id_position' => 2,
//             'id_division' => 2,
//             'id_role' => 1,
//             'photo' => null,
//             'phone' => '12345678',
//             'status' => true
//         ]);

//         User::factory()->create([
//             'name' => 'General manager',
//             'email' => 'gm@transtama.com',
//             'password' => bcrypt('gm123'),
//             'id_position' => 3,
//             'id_division' => 3,
//             'id_role' => 1,
//             'photo' => null,
//             'phone' => '12345678',
//             'status' => true
//         ]);
        
//         User::factory()->create([
//             'name' => 'CS Manager',
//             'email' => 'csman@transtama.com',
//             'password' => bcrypt('csman123'),
//             'id_position' => 5,
//             'id_division' => 4,
//             'id_role' => 1,
//             'photo' => null,
//             'phone' => '12345678',
//             'status' => true
//         ]);

//         // User::factory()->create([
//         //     'name' => 'Key Account',
//         //     'email' => 'ka@transtama.com',
//         //     'password' => bcrypt('keyaccount123'),
//         //     'id_position' => 2,
//         //     'id_division' => 2,
//         //     'id_role' => 1,
//         //     'photo' => null,
//         //     'phone' => '12345678',
//         //     'status' => true
//         // ]);

//         // User::factory()->create([
//         //     'name' => 'Sales',
//         //     'email' => 'sales@transtama.com',
//         //     'password' => bcrypt('sales123'),
//         //     'id_position' => 2,
//         //     'id_division' => 2,
//         //     'id_role' => 1,
//         //     'photo' => null,
//         //     'phone' => '12345678',
//         //     'status' => true
//         // ]);


//         // User::factory()->create([
//         //     'name' => 'Operasional',
//         //     'email' => 'ops@transtama.com',
//         //     'password' => bcrypt('operasional123'),
//         //     'id_position' => 2,
//         //     'id_division' => 2,
//         //     'id_role' => 1,
//         //     'photo' => null,
//         //     'phone' => '12345678',
//         //     'status' => true
//         // ]);

//         // User::factory()->create([
//         //     'name' => 'Finance',
//         //     'email' => 'finance@transtama.com',
//         //     'password' => bcrypt('finance123'),
//         //     'id_position' => 2,
//         //     'id_division' => 2,
//         //     'id_role' => 1,
//         //     'photo' => null,
//         //     'phone' => '12345678',
//         //     'status' => true
//         // ]);
//     }
// }
