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
        Schema::create('delivery_personnels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company'); // Grab, Shopee, etc.
            $table->string('vehicle_type'); // Motorcycle, Car, Van, Lorry
            $table->string('vehicle_number');
            $table->string('phone');
            $table->string('ic_number')->unique();
            $table->string('photo')->nullable();
            $table->string('status')->default('Active'); // Active, Banned
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_personnels');
    }
};
