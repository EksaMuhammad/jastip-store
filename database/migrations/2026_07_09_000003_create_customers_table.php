<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('name');
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['phone_number', 'deleted_at'], 'uniq_phone_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
