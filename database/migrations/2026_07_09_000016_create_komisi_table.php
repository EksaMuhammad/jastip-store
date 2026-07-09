<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'deleted_at'], 'uniq_komisi_order_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komisi');
    }
};
