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
        if (Schema::hasTable('visits') && !Schema::hasColumn('visits', 'host_name')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->string('host_name')->nullable()->after('purpose');
            });
        }

        if (Schema::hasTable('delivery_logs') && !Schema::hasColumn('delivery_logs', 'host_name')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                $table->string('host_name')->nullable()->after('destination');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visits') && Schema::hasColumn('visits', 'host_name')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('host_name');
            });
        }

        if (Schema::hasTable('delivery_logs') && Schema::hasColumn('delivery_logs', 'host_name')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                $table->dropColumn('host_name');
            });
        }
    }
};
