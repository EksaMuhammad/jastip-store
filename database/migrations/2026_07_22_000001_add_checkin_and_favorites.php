<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan kolom check-in ke tabel jastiper
        Schema::table('jastiper', function (Blueprint $table) {
            $table->string('checkin_location')->nullable()->after('is_available');
            $table->timestamp('checked_in_at')->nullable()->after('checkin_location');
        });

        // Buat tabel customer_favorites
        Schema::create('customer_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('jastiper_id')->constrained('jastiper')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['customer_id', 'jastiper_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_favorites');

        Schema::table('jastiper', function (Blueprint $table) {
            $table->dropColumn(['checkin_location', 'checked_in_at']);
        });
    }
};
