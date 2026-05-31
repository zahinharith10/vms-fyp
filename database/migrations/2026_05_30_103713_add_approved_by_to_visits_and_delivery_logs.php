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
        if (Schema::hasTable('visits') && !Schema::hasColumn('visits', 'approved_by')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->string('approved_by')->nullable()->after('host_name');
            });
        }

        if (Schema::hasTable('delivery_logs') && !Schema::hasColumn('delivery_logs', 'approved_by')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                $table->string('approved_by')->nullable()->after('host_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visits') && Schema::hasColumn('visits', 'approved_by')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('approved_by');
            });
        }

        if (Schema::hasTable('delivery_logs') && Schema::hasColumn('delivery_logs', 'approved_by')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                $table->dropColumn('approved_by');
            });
        }
    }
};
