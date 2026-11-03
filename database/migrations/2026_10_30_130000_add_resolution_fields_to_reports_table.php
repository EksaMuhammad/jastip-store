<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->decimal('refund_amount', 12, 2)->nullable()->after('evidence_image');
            $table->string('resolution_type')->nullable()->after('status'); // refund_full, refund_partial, reject_complaint
            $table->text('investigation_notes')->nullable()->after('admin_decision');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'resolution_type', 'investigation_notes']);
        });
    }
};
