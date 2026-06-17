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
        if (Schema::hasTable('visits') && !Schema::hasColumn('visits', 'approved_at')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            });
        }

        if (Schema::hasTable('delivery_logs') && !Schema::hasColumn('delivery_logs', 'approved_at')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visits') && Schema::hasColumn('visits', 'approved_at')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }

        if (Schema::hasTable('delivery_logs') && Schema::hasColumn('delivery_logs', 'approved_at')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
