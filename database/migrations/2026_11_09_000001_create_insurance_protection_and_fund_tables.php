<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah field asuransi pada tabel orders
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_insurance')->default(false)->after('weight_category');
            $table->decimal('insurance_fee', 12, 2)->default(0)->after('has_insurance');
            $table->boolean('insurance_claimed')->default(false)->after('insurance_fee');
            $table->decimal('insurance_claim_amount', 12, 2)->nullable()->after('insurance_claimed');
            $table->decimal('jastiper_debt_amount', 12, 2)->default(0)->after('insurance_claim_amount');
        });

        // 2. Tambah field klaim asuransi pada tabel reports
        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('is_insurance_claim')->default(false)->after('cs_claim_status');
            $table->decimal('insurance_payout', 12, 2)->default(0)->after('is_insurance_claim');
            $table->decimal('jastiper_charged_amount', 12, 2)->default(0)->after('insurance_payout');
        });

        // 3. Tabel Kas & Transaksi Pool Dana Asuransi Platform
        Schema::create('insurance_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['premi_masuk', 'klaim_keluar', 'pelunasan_jastiper']);
            $table->decimal('amount', 12, 2);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_transactions');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['is_insurance_claim', 'insurance_payout', 'jastiper_charged_amount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['has_insurance', 'insurance_fee', 'insurance_claimed', 'insurance_claim_amount', 'jastiper_debt_amount']);
        });
    }
};

