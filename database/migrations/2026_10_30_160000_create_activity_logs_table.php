<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable()->index(); // customer, jastiper, admin, system
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('category')->default('pesanan')->index(); // pesanan, pengantaran, harga_toko, komplain_tiket, chat_cs, keuangan, sistem
            $table->string('action')->index(); // order_created, offer_submitted, deal_formed, status_changed, price_adjusted, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
