<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Drop and recreate chats table with the updated columns for SQLite compatibility
            Schema::dropIfExists('chats');
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders');
                $table->enum('sender_role', ['customer', 'jastiper', 'system']);
                $table->unsignedBigInteger('sender_id');
                $table->enum('message_type', ['text', 'action_button']);
                $table->text('message');
                $table->string('attachment_path')->nullable();
                $table->string('action_type')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['order_id', 'created_at'], 'chats_order_id_created_at_index');
            });
        } else {
            // ================= CHATS: lampiran foto + kolom pendukung =================
            Schema::table('chats', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('message');
            });

            // ================= CHATS: index performa untuk riwayat + polling =================
            Schema::table('chats', function (Blueprint $table) {
                $table->index(['order_id', 'created_at'], 'chats_order_id_created_at_index');
            });

            // ================= CHATS: tambah opsi 'system' ke enum sender_role =================
            DB::statement("ALTER TABLE chats MODIFY COLUMN sender_role ENUM('customer', 'jastiper', 'system') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Revert chats table to original schema in SQLite
            Schema::dropIfExists('chats');
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders');
                $table->enum('sender_role', ['customer', 'jastiper']);
                $table->unsignedBigInteger('sender_id');
                $table->enum('message_type', ['text', 'action_button']);
                $table->text('message');
                $table->string('action_type')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('chats', function (Blueprint $table) {
                $table->dropIndex('chats_order_id_created_at_index');
            });

            Schema::table('chats', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });

            DB::statement("ALTER TABLE chats MODIFY COLUMN sender_role ENUM('customer', 'jastiper') NOT NULL");
        }
    }
};