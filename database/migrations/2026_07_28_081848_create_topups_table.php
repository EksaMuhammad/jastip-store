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
        Schema::create('topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['menunggu', 'berhasil', 'gagal'])->default('menunggu');
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('method')->nullable();
            $table->string('channel')->nullable();
            $table->string('va_number')->nullable();
            $table->text('qr_string')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('raw_webhook_payload')->nullable();
            $table->timestamp('payment_deadline')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topups');
    }
};
