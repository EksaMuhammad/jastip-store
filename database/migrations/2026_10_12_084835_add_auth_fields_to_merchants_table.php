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
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('name');
            $table->string('password')->nullable()->after('email');
            $table->string('phone_number')->nullable()->after('password');
            $table->rememberToken()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['email', 'password', 'phone_number', 'remember_token']);
        });
    }
};
