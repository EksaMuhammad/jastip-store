<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('reporter_role', ['customer', 'jastiper']);
            $table->unsignedBigInteger('reporter_id');
            $table->string('category');
            $table->text('description');
            $table->string('evidence_image')->nullable();
            $table->enum('status', ['menunggu', 'selesai'])->default('menunggu');
            $table->string('admin_decision')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('admins');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
