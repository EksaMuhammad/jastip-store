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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('item_type')->default('catalog')->after('product_id'); // catalog, custom_buy, delivery_service
            $table->string('category')->nullable()->after('item_type'); // beli-antar, ambil-antar, toko-kirim, dokumen, multi-stop, kirim-pihak-ketiga
            $table->string('name')->nullable()->after('category');
            $table->text('description')->nullable()->after('name');
            $table->decimal('estimated_price', 12, 2)->default(0)->after('description');
            $table->string('origin_address')->nullable()->after('estimated_price');
            $table->decimal('origin_lat', 10, 8)->nullable()->after('origin_address');
            $table->decimal('origin_lng', 11, 8)->nullable()->after('origin_lat');
            $table->string('destination_address')->nullable()->after('origin_lng');
            $table->decimal('destination_lat', 10, 8)->nullable()->after('destination_address');
            $table->decimal('destination_lng', 11, 8)->nullable()->after('destination_lat');
            $table->string('recipient_name')->nullable()->after('destination_lng');
            $table->string('recipient_phone')->nullable()->after('recipient_name');
            $table->string('reference_photo')->nullable()->after('recipient_phone');
            $table->string('weight_category')->nullable()->after('reference_photo');
            $table->string('courier_agency')->nullable()->after('weight_category');
            $table->json('stops_data')->nullable()->after('courier_agency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_type',
                'category',
                'name',
                'description',
                'estimated_price',
                'origin_address',
                'origin_lat',
                'origin_lng',
                'destination_address',
                'destination_lat',
                'destination_lng',
                'recipient_name',
                'recipient_phone',
                'reference_photo',
                'weight_category',
                'courier_agency',
                'stops_data',
            ]);
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
