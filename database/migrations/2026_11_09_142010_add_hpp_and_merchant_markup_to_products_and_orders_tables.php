<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah HPP & Opsi Auto-Markup pada tabel products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('hpp_price', 12, 2)->nullable()->after('description');
            $table->decimal('discount_hpp_price', 12, 2)->nullable()->after('hpp_price');
            $table->boolean('is_auto_markup')->default(true)->after('discount_hpp_price');
        });

        // 2. Tambah HPP & Nilai Markup pada tabel order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('hpp_price', 12, 2)->nullable()->after('estimated_price');
            $table->decimal('markup_amount', 12, 2)->default(0)->after('hpp_price');
        });

        // 3. Tambah Keuntungan Margin Barang Platform pada tabel orders
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('merchant_markup_profit', 12, 2)->default(0)->after('insurance_claim_amount');
        });

        // 4. Tambah Nilai Margin Barang pada tabel komisi
        Schema::table('komisi', function (Blueprint $table) {
            $table->decimal('item_markup_amount', 12, 2)->default(0)->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('komisi', function (Blueprint $table) {
            $table->dropColumn('item_markup_amount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('merchant_markup_profit');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['hpp_price', 'markup_amount']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hpp_price', 'discount_hpp_price', 'is_auto_markup']);
        });
    }
};
