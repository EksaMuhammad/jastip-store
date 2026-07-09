<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('jastiper_id')->nullable()->constrained('jastiper');
            $table->foreignId('wilayah_id')->constrained('wilayah');
            $table->enum('category', [
                'beli-antar',
                'ambil-antar',
                'toko-kirim',
                'dokumen',
                'multi-stop',
                'kirim-pihak-ketiga'
            ]);
            $table->enum('weight_category', ['ringan', 'sedang', 'berat']);
            $table->text('description');
            $table->string('reference_photo')->nullable();
            $table->text('origin_address')->nullable();
            $table->text('destination_address');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->decimal('estimated_fare', 12, 2);
            $table->decimal('agreed_fare', 12, 2)->nullable();
            $table->enum('status', [
                'menunggu_tawaran',
                'ada_tawaran',
                'deal',
                'menunggu_pembayaran',
                'diproses',
                'barang_diambil',
                'sedang_diantar',
                'tiba_tujuan',
                'diterima',
                'selesai',
                'dibatalkan',
                'bermasalah'
            ]);
            $table->enum('cancelled_by_role', ['customer', 'jastiper', 'admin', 'system'])->nullable();
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
