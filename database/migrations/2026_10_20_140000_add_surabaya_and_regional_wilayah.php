<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $regions = [
            ['name' => 'Surabaya Kota', 'default_radius_km' => 15.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sidoarjo', 'default_radius_km' => 15.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gresik', 'default_radius_km' => 15.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jawa Timur (Antar Kota)', 'default_radius_km' => 100.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nasional / Seluruh Indonesia', 'default_radius_km' => 250.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($regions as $region) {
            $exists = DB::table('wilayah')->where('name', $region['name'])->exists();
            if (!$exists) {
                DB::table('wilayah')->insert($region);
            }
        }
    }

    public function down(): void
    {
        DB::table('wilayah')->whereIn('name', [
            'Surabaya Kota',
            'Sidoarjo',
            'Gresik',
            'Jawa Timur (Antar Kota)',
            'Nasional / Seluruh Indonesia',
        ])->delete();
    }
};
