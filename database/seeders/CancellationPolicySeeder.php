<?php

namespace Database\Seeders;

use App\Models\CancellationPolicy;
use Illuminate\Database\Seeder;

class CancellationPolicySeeder extends Seeder
{
    public function run(): void
    {
        CancellationPolicy::create([
            'stage' => 'sebelum_diambil',
            'refund_percentage' => 100.00,
            'jastiper_compensation_percentage' => 0.00,
            'description' => 'Dibatalkan sebelum jastiper mulai membeli/mengambil barang',
        ]);

        CancellationPolicy::create([
            'stage' => 'setelah_diambil',
            'refund_percentage' => 70.00,
            'jastiper_compensation_percentage' => 30.00,
            'description' => 'Dibatalkan setelah jastiper sudah membeli/mengambil barang',
        ]);
    }
}
