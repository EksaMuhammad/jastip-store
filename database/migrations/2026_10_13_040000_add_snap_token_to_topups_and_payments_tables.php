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
        if (Schema::hasTable('topups') && !Schema::hasColumn('topups', 'snap_token')) {
            Schema::table('topups', function (Blueprint $table) {
                $table->string('snap_token')->nullable()->after('gateway_reference');
            });
        }

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'snap_token')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('snap_token')->nullable()->after('gateway_reference');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('topups') && Schema::hasColumn('topups', 'snap_token')) {
            Schema::table('topups', function (Blueprint $table) {
                $table->dropColumn('snap_token');
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'snap_token')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('snap_token');
            });
        }
    }
};
