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
        $wilayah = Wilayah::create([
            'name' => 'Jakarta Selatan',
            'default_radius_km' => 5.00,
            'is_active' => true,
        ]);

        // 3. Seed Admins
        Admin::create([
            'email' => 'admin@jastipkuy.com',
            'password_hash' => Hash::make('password123'),
            'name' => 'Admin JastipKuy',
        ]);

        // 4. Seed Customers
        Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Utomo',
        ]);

        // 5. Seed Jastiper
        Jastiper::create([
            'phone_number' => '089876543210',
            'name' => 'Siti Aminah',
            'verification_status' => 'approved',
            'wilayah_id' => $wilayah->id,
            'radius_km' => 5.00,
            'is_available' => true,
        ]);
    }
}
