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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['flyer', 'ads'])->default('flyer');
            $table->string('badge_1')->nullable();
            $table->string('badge_2')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('terms')->nullable();
            $table->string('button_text')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
