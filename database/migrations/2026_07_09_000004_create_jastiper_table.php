<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jastiper', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('name');
            $table->string('password')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->enum('verification_status', ['belum', 'menunggu', 'approved', 'rejected'])->default('belum');
            $table->foreignId('wilayah_id')->constrained('wilayah');
            $table->decimal('radius_km', 8, 2);
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['phone_number', 'deleted_at'], 'uniq_jastiper_phone_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jastiper');
    }
};
