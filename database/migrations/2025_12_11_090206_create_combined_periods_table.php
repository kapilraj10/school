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
        Schema::create('combined_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // e.g., "Combined PE - Class 1A & 1B"
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_term_id')->constrained()->onDelete('cascade');
            $table->json('class_room_ids'); // Array of class room IDs
            $table->integer('day'); // 1-5 for Monday-Friday
            $table->integer('period'); // 1-8 for periods
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly'])->default('weekly');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combined_periods');
    }
};
