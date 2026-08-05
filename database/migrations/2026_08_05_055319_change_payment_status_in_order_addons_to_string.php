<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enum alteration is tricky, let's use raw SQL since we know it's MySQL
        DB::statement("ALTER TABLE order_addons MODIFY COLUMN payment_status VARCHAR(255) DEFAULT 'pending_jastiper'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE order_addons MODIFY COLUMN payment_status ENUM('menunggu', 'lunas', 'gagal') DEFAULT 'menunggu'");
    }
};
