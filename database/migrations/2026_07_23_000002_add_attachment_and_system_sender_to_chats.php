<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ================= CHATS: lampiran foto + kolom pendukung =================
        Schema::table('chats', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('message');
        });

        // ================= CHATS: index performa untuk riwayat + polling =================
        // Chat di-fetch berkala (polling tiap ~4-5 detik di Tahap 5), jadi query
        // "ambil semua chat untuk order X urut waktu" harus cepat.
        Schema::table('chats', function (Blueprint $table) {
            $table->index(['order_id', 'created_at'], 'chats_order_id_created_at_index');
        });

        // ================= CHATS: tambah opsi 'system' ke enum sender_role =================
        // Laravel Blueprint tidak native support modify enum tanpa doctrine/dbal,
        // jadi pakai raw ALTER. Nilai lama (customer/jastiper) tetap valid, ini
        // cuma nambah 1 opsi baru ke enum, bukan mengganti definisi.
        DB::statement("ALTER TABLE chats MODIFY COLUMN sender_role ENUM('customer', 'jastiper', 'system') NOT NULL");

        // Keputusan desain (didokumentasikan supaya sesi lanjutan tidak tanya ulang):
        // sender_id TETAP unsignedBigInteger NOT NULL (tidak dibuat nullable).
        // Untuk pesan sistem, ChatService::sendSystemMessage() akan mengisi
        // sender_id = 0 sebagai konvensi "system" — bukan NULL. Alasan: kolom
        // sender_id dipakai bareng sender_role sebagai morphTo pair, membuatnya
        // nullable membuka celah pesan customer/jastiper tanpa sender_id yang
        // valid (bug tersembunyi) padahal itu tidak boleh terjadi. Nilai 0 aman
        // karena auto_increment id sungguhan mulai dari 1, jadi 0 tidak akan
        // pernah bentrok dengan customer_id/jastiper_id asli manapun.
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex('chats_order_id_created_at_index');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });

        // Revert enum sender_role ke 2 opsi semula.
        // PERHATIAN: rollback ini akan GAGAL kalau sudah ada baris dengan
        // sender_role = 'system' di tabel (MySQL menolak MODIFY enum yang
        // menghapus opsi yang masih dipakai data). Kalau perlu rollback
        // setelah fitur chat sistem sudah jalan di production, hapus/migrasi
        // dulu baris sender_role='system' secara manual sebelum rollback.
        DB::statement("ALTER TABLE chats MODIFY COLUMN sender_role ENUM('customer', 'jastiper') NOT NULL");
    }
};