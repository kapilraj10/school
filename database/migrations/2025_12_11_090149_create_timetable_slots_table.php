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
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('academic_term_id')->constrained()->onDelete('cascade');
            $table->foreignId('combined_period_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('day'); 
            $table->integer('period');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', ['regular', 'break', 'lunch', 'assembly', 'combined'])->default('regular');
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_combined')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['class_room_id', 'day', 'period', 'academic_term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
