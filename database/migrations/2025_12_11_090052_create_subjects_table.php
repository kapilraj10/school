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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->string('class_range')->nullable();
            $table->enum('type', ['core', 'elective', 'co_curricular']);
            $table->integer('weekly_periods')->default(4);
            $table->integer('min_periods_per_week')->default(1);
            $table->integer('max_periods_per_week')->default(1);
            $table->string('level', 50)->nullable(); // basic_1_3, basic_4_8, secondary_9_10
            $table->enum('single_combined', ['single', 'combined'])->default('single');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
