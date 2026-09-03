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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->boolean('is_catalog_order')->default(false)->after('merchant_id');
            $table->decimal('downpayment_amount', 12, 2)->default(0)->after('status');
            $table->decimal('cash_amount', 12, 2)->default(0)->after('downpayment_amount');
            
            // Allow description to be nullable for catalog orders since they use order_items
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn(['merchant_id', 'is_catalog_order', 'downpayment_amount', 'cash_amount']);
            $table->text('description')->nullable(false)->change();
        });
    }
};
