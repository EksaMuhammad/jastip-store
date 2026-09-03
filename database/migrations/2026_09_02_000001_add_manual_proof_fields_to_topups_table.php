<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topups', function (Blueprint $table) {
            $table->string('proof_image')->nullable()->after('raw_webhook_payload');
            $table->foreignId('verified_by_admin_id')->nullable()->after('verified_at')
                ->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('topups', function (Blueprint $table) {
            $table->dropForeign(['verified_by_admin_id']);
            $table->dropColumn(['proof_image', 'verified_by_admin_id']);
        });
    }
};