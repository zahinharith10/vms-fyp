<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_logs', function (Blueprint $table) {
            $table->foreignId('delivery_run_id')
                ->nullable()
                ->after('delivery_personnel_id')
                ->constrained('delivery_runs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_run_id');
        });
    }
};
