<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_personnel_id')->constrained('delivery_personnels')->cascadeOnDelete();
            $table->string('type'); // single | multi
            $table->string('status')->default('Pending');
            $table->timestamp('entry_time')->nullable();
            $table->timestamp('exit_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_runs');
    }
};
