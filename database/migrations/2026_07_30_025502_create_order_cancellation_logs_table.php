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
        Schema::create('order_cancellation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('cancelled_by_role'); // Customer, Jastiper, Admin
            $table->unsignedBigInteger('cancelled_by_id');
            $table->string('stage'); // e.g., 'menunggu_pembayaran', 'diproses', 'barang_diambil', etc.
            $table->text('reason')->nullable();
            $table->decimal('total_refund', 12, 2)->default(0);
            $table->decimal('jastiper_compensation', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cancellation_logs');
    }
};
