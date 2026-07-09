<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('jastiper_id')->constrained('jastiper');
            $table->tinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'deleted_at'], 'uniq_rating_order_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
