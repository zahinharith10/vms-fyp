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
        // First update all null values to a placeholder to avoid migration failure
        \DB::table('visitors')->whereNull('vehicle_number')->update(['vehicle_number' => '-']);

        Schema::table('visitors', function (Blueprint $table) {
            $table->string('vehicle_number')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('vehicle_number')->nullable()->change();
        });
    }
};
