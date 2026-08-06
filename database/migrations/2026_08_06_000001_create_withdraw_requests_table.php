<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Brief Sprint 8 Bagian 3 §2.1: tabel pengajuan withdraw jastiper.
     * Saldo TIDAK dipotong di titik pengajuan (status 'menunggu'), baru
     * dipotong dari wallet saat admin approve — lihat WithdrawService.
     */
    public function up(): void
    {
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jastiper_id')->constrained('jastiper');
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->decimal('amount', 12, 2);
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_account_holder');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('processed_by_admin_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};