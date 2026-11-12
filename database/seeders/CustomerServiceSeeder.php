<?php

namespace Database\Seeders;

use App\Models\CustomerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerServiceSeeder extends Seeder
{
    public function run(): void
    {
        $officers = [
            [
                'name' => 'Budi Santoso - CS Support 1',
                'email' => 'cs1@jastipkuy.com',
                'password_hash' => Hash::make('password123'),
                'phone_number' => '081234567895',
                'is_active' => true,
                'is_online' => true,
                'work_status' => 'tersedia',
                'last_active_at' => now(),
            ],
            [
                'name' => 'Dewi Lestari - CS Support 2',
                'email' => 'cs2@jastipkuy.com',
                'password_hash' => Hash::make('password123'),
                'phone_number' => '081234567896',
                'is_active' => true,
                'is_online' => true,
                'work_status' => 'tersedia',
                'last_active_at' => now(),
            ],
            [
                'name' => 'Ahmad Fauzi - CS Dispute 3',
                'email' => 'cs3@jastipkuy.com',
                'password_hash' => Hash::make('password123'),
                'phone_number' => '081234567897',
                'is_active' => true,
                'is_online' => false,
                'work_status' => 'istirahat',
                'last_active_at' => null,
            ],
        ];

        foreach ($officers as $data) {
            CustomerService::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}

