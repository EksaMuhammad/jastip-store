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
        Schema::table('topups', function (Blueprint $table) {
            $table->string('status', 40)->default('menunggu_approval')->change();
            $table->foreignId('approved_by_admin_id')->nullable()->after('verified_by_admin_id')
                ->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('verified_at');
            $table->text('rejection_note')->nullable()->after('proof_image');
        });
    }

    public function down(): void
    {
        Schema::table('topups', function (Blueprint $table) {
            $table->dropForeign(['approved_by_admin_id']);
            $table->dropColumn(['approved_by_admin_id', 'approved_at', 'rejection_note']);
        });
    }
};
