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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('employee_id', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->json('subject_ids')->nullable(); // Array of subject IDs they can teach
            $table->integer('max_periods_per_day')->default(6);
            $table->integer('max_periods_per_week')->default(30);
            $table->json('available_days')->nullable();
            $table->json('available_periods')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
