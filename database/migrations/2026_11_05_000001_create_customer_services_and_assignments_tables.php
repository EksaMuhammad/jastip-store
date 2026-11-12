<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Petugas Customer Service (CS)
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('phone_number')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->enum('work_status', ['tersedia', 'sibuk', 'istirahat'])->default('tersedia');
            $table->timestamp('last_active_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_online', 'work_status']);
        });

        // 2. Tabel Antrean & Penugasan Percakapan Chat Pelanggan
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('assigned_cs_id')->nullable()->constrained('customer_services')->nullOnDelete();
            $table->enum('status', ['menunggu', 'aktif', 'selesai'])->default('menunggu');
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index(['status', 'assigned_cs_id']);
            $table->index('last_message_at');
        });

        // 3. Tambahkan referensi Petugas CS ke support_messages
        Schema::table('support_messages', function (Blueprint $table) {
            $table->foreignId('cs_id')->nullable()->after('admin_id')->constrained('customer_services')->nullOnDelete();
        });

        // 4. Tambahkan referensi Petugas CS ke reports (Komplain)
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('assigned_cs_id')->nullable()->after('decided_by')->constrained('customer_services')->nullOnDelete();
            $table->enum('cs_claim_status', ['unassigned', 'in_progress', 'resolved'])->default('unassigned')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['assigned_cs_id']);
            $table->dropColumn(['assigned_cs_id', 'cs_claim_status']);
        });

        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropForeign(['cs_id']);
            $table->dropColumn('cs_id');
        });

        Schema::dropIfExists('support_conversations');
        Schema::dropIfExists('customer_services');
    }
};

