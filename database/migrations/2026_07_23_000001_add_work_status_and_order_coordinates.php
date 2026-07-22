<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ================= JASTIPER: status kerja 3-state =================
        // tersedia = online & aktif menerima order baru di feed
        // standby  = online tapi berhenti sementara menerima order baru (istirahat)
        // offline  = benar-benar offline, tidak bisa dibooking sama sekali
        Schema::table('jastiper', function (Blueprint $table) {
            $table->enum('work_status', ['tersedia', 'standby', 'offline'])
                ->default('offline')
                ->after('is_available');
        });

        // Backfill data lama: is_available=true -> tersedia, false -> offline
        DB::table('jastiper')->where('is_available', true)->update(['work_status' => 'tersedia']);
        DB::table('jastiper')->where('is_available', false)->update(['work_status' => 'offline']);

        // ================= ORDERS: koordinat asal & tujuan =================
        // Sebelumnya cuma ada alamat teks, tidak ada lat/lng tersimpan.
        // Kolom ini wajib ada supaya query filter radius bisa jalan di server.
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('origin_lat', 10, 8)->nullable()->after('origin_address');
            $table->decimal('origin_lng', 11, 8)->nullable()->after('origin_lat');
            $table->decimal('destination_lat', 10, 8)->nullable()->after('destination_address');
            $table->decimal('destination_lng', 11, 8)->nullable()->after('destination_lat');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['origin_lat', 'origin_lng', 'destination_lat', 'destination_lng']);
        });

        Schema::table('jastiper', function (Blueprint $table) {
            $table->dropColumn('work_status');
        });
    }
};