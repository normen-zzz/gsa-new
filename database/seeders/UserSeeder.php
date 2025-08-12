<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default user
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@transtama.com',
            'password' => bcrypt('admin123'),
            'id_position' => 1,
            'id_division' => 1,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);

        User::factory()->create([
            'name' => 'sales',
            'email' => 'sales@transtama.com',
            'password' => bcrypt('admin123'),
            'id_position' => 4,
            'id_division' => 5,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);

        User::factory()->create([
            'name' => 'cs',
            'email' => 'cs@transtama.com',
            'password' => bcrypt('admin123'),
            'id_position' => 4,
            'id_division' => 3,
            'id_role' => 1,
            'photo' => null,
            'phone' => '12345678',
            'status' => true
        ]);

    }
}
