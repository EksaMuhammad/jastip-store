<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jastiper_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jastiper_id')->constrained('jastiper');
            $table->string('ktp_image');
            $table->string('selfie_image');
            $table->enum('status', ['menunggu', 'approved', 'rejected'])->default('menunggu');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jastiper_verifications');
    }
};
