<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('method', ['transfer', 'wallet']);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['menunggu', 'lunas', 'gagal'])->default('menunggu');
            $table->timestamp('payment_deadline')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('proof_image')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
