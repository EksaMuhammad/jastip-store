<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('multi_stops')->nullable()->after('destination_lng');
            $table->string('courier_agency')->nullable()->after('multi_stops'); // Untuk kirim-pihak-ketiga (JNE, J&T, SiCepat, dll)
            $table->string('tracking_number')->nullable()->after('courier_agency'); // Nomor resi ekspedisi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['multi_stops', 'courier_agency', 'tracking_number']);
        });
    }
};
