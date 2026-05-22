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
        Schema::table('delivery_logs', function (Blueprint $table) {
            $table->string('status')->default('Pending')->after('delivery_personnel_id');
            $table->timestamp('entry_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_logs', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->timestamp('entry_time')->useCurrent()->change();
        });
    }
};
