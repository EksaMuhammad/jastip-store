<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 1 — Brief: Fitur Halaman Pembayaran Wajib (Virtual Escrow), §1.2
 *
 * Menambahkan kolom-kolom yang dibutuhkan untuk flow VA + QRIS + webhook Midtrans
 * + fallback verifikasi manual admin, tanpa mengubah kolom yang sudah ada (method,
 * amount, status lama, payment_deadline, gateway_reference, proof_image, verified_at).
 *
 * Kolom baru:
 * - channel                 : bedakan 'bank_transfer_va' vs 'qris' di bawah method 'transfer'
 * - va_number / qr_string   : data yang ditampilkan ke UI
 * - gateway_transaction_id  : ID transaksi dari Midtrans (beda dari gateway_reference)
 * - raw_response            : payload mentah respons create VA/QRIS (audit/debug)
 * - raw_webhook_payload     : payload mentah webhook Midtrans (audit/debug)
 * - verified_by_admin_id    : FK ke admins, siapa yang approve verifikasi manual
 *
 * Enum status ditambah 1 nilai baru: 'kedaluwarsa' (auto-expire karena timeout,
 * beda semantik dari 'gagal' yang berarti direject gateway/admin).
 *
 * MySQL tidak butuh doctrine/dbal untuk MODIFY enum via raw SQL (project ini tidak
 * meng-install doctrine/dbal). SQLite (dipakai di test, lihat phpunit.xml) tidak
 * mendukung ALTER pada CHECK constraint sama sekali, jadi tabel di-recreate dengan
 * skema final lalu data lama dipindahkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->upSqlite();
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('channel')->nullable()->after('method');
            $table->string('va_number')->nullable()->after('channel');
            $table->string('qr_string')->nullable()->after('va_number');
            $table->string('gateway_transaction_id')->nullable()->after('gateway_reference');
            $table->json('raw_response')->nullable()->after('gateway_transaction_id');
            $table->json('raw_webhook_payload')->nullable()->after('raw_response');
            $table->foreignId('verified_by_admin_id')->nullable()->after('verified_at')
                ->constrained('admins')->nullOnDelete();
        });

        // Widen enum status: 'menunggu', 'lunas', 'gagal', 'kedaluwarsa'
        DB::statement("ALTER TABLE payments MODIFY status ENUM('menunggu', 'lunas', 'gagal', 'kedaluwarsa') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->downSqlite();
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by_admin_id']);
            $table->dropColumn([
                'channel',
                'va_number',
                'qr_string',
                'gateway_transaction_id',
                'raw_response',
                'raw_webhook_payload',
                'verified_by_admin_id',
            ]);
        });

        DB::statement("ALTER TABLE payments MODIFY status ENUM('menunggu', 'lunas', 'gagal') NOT NULL DEFAULT 'menunggu'");
    }

    /**
     * SQLite tidak bisa ALTER CHECK constraint (enum) maupun tambah kolom lewat
     * jalur normal tanpa doctrine/dbal untuk kasus ini secara konsisten, jadi
     * tabel di-recreate penuh dengan skema final lalu data lama disalin.
     */
    private function upSqlite(): void
    {
        Schema::create('payments_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('method', ['transfer', 'wallet']);
            $table->string('channel')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['menunggu', 'lunas', 'gagal', 'kedaluwarsa'])->default('menunggu');
            $table->timestamp('payment_deadline')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('va_number')->nullable();
            $table->string('qr_string')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('raw_webhook_payload')->nullable();
            $table->string('proof_image')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('INSERT INTO payments_new (
                id, order_id, method, amount, status, payment_deadline,
                gateway_reference, proof_image, verified_at, created_at, updated_at, deleted_at
            )
            SELECT
                id, order_id, method, amount, status, payment_deadline,
                gateway_reference, proof_image, verified_at, created_at, updated_at, deleted_at
            FROM payments');

        Schema::drop('payments');
        Schema::rename('payments_new', 'payments');
    }

    private function downSqlite(): void
    {
        Schema::create('payments_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('method', ['transfer', 'wallet']);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['menunggu', 'lunas', 'gagal'])->default('menunggu');
            $table->timestamp('payment_deadline')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('proof_image')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('INSERT INTO payments_old (
                id, order_id, method, amount, status, payment_deadline,
                gateway_reference, proof_image, verified_at, created_at, updated_at, deleted_at
            )
            SELECT
                id, order_id, method, amount, status, payment_deadline,
                gateway_reference, proof_image, verified_at, created_at, updated_at, deleted_at
            FROM payments');

        Schema::drop('payments');
        Schema::rename('payments_old', 'payments');
    }
};