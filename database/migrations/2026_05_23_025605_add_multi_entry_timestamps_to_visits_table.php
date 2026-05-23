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
        Schema::table('visits', function (Blueprint $table) {
            $table->timestamp('first_check_in_time')->nullable()->after('check_in_time');
            $table->timestamp('first_check_out_time')->nullable()->after('check_out_time');
            $table->timestamp('second_check_in_time')->nullable()->after('first_check_in_time');
            $table->timestamp('second_check_out_time')->nullable()->after('first_check_out_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn([
                'first_check_in_time',
                'first_check_out_time',
                'second_check_in_time',
                'second_check_out_time',
            ]);
        });
    }
};
