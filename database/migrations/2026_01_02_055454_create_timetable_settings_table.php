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
        // Main settings table for global timetable configuration
        Schema::create('timetable_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json, array
            $table->string('group')->default('general'); // general, periods, classes, subjects
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Class-Subject pivot table for per-class subject configuration
        Schema::create('class_subject_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->integer('min_periods_per_week')->default(1);
            $table->integer('max_periods_per_week')->default(6);
            $table->integer('weekly_periods')->default(4);
            $table->enum('single_combined', ['single', 'combined'])->default('single');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(5); // 1-10, higher = more important
            $table->timestamps();

            $table->unique(['class_room_id', 'subject_id']);
        });

        // Class ranges configuration table
        Schema::create('class_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "1 - 4", "5 - 7", "8", "9 - 10", "11 - 12"
            $table->string('display_name'); // e.g., "Class 1-4", "Class 5-7"
            $table->integer('start_class');
            $table->integer('end_class');
            $table->integer('periods_per_day')->default(8);
            $table->integer('periods_per_week')->default(48);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['start_class', 'end_class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subject_settings');
        Schema::dropIfExists('class_ranges');
        Schema::dropIfExists('timetable_settings');
    }
};
