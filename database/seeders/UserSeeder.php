<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Customer Dummy
        $customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Customer',
        ]);
        Wallet::create([
            'owner_id' => $customer->id,
            'owner_role' => 'customer',
            'balance' => 500000, // 500k for testing
        ]);

        // 2. Jastiper Dummy
        $jastiper = Jastiper::create([
            'phone_number' => '081234567891',
            'name' => 'Siti Jastiper',
            'wilayah_id' => 11, // Malang Kota
            'verification_status' => 'approved',
            'is_available' => true,
            'radius_km' => 5.00,
        ]);
        Wallet::create([
            'owner_id' => $jastiper->id,
            'owner_role' => 'jastiper',
            'balance' => 100000,
        ]);
    }
}
