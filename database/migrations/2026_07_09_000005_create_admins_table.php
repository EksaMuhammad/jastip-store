<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('password_hash');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['email', 'deleted_at'], 'uniq_admin_email_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
