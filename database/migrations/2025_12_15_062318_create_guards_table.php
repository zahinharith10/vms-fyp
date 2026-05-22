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
        Schema::create('guards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('employee_id')->unique();
            $table->string('ic_number')->unique();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('photo')->nullable(); // Path to photo
            $table->string('shift'); // Morning, Night, Rotating
            $table->string('status')->default('Active'); // Active, Inactive
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guards');
    }
};
