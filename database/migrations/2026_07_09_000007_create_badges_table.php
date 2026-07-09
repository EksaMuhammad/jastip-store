<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jastiper_id')->constrained('jastiper');
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->integer('avg_response_time_minutes')->default(0);
            $table->integer('total_completed_orders')->default(0);
            $table->enum('badge_level', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jastiper_id', 'deleted_at'], 'uniq_badge_jastiper_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
