<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Run CancellationPolicySeeder
        $this->call(CancellationPolicySeeder::class);

        // 2. Seed Wilayah
        \DB::table('wilayah')->insert([
            ['id' => 11, 'name' => 'Malang Kota', 'default_radius_km' => 5.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => 'Malang Kota', 'default_radius_km' => 5.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'name' => 'Malang Kota', 'default_radius_km' => 5.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Seed Admins
        Admin::create([
            'email' => 'admin@jastipkuy.com',
            'password_hash' => Hash::make('password123'),
            'name' => 'Admin JastipKuy',
        ]);
    }
}
