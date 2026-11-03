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
            $table->string('pickup_photo')->nullable()->after('reference_photo');
            $table->timestamp('pickup_time')->nullable()->after('pickup_photo');
            $table->string('delivery_photo')->nullable()->after('pickup_time');
            $table->timestamp('delivery_time')->nullable()->after('delivery_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_photo', 'pickup_time', 'delivery_photo', 'delivery_time']);
        });
    }
};

