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
        Schema::create('conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_term_id')->constrained()->onDelete('cascade');
            $table->string('type'); // teacher_conflict, unavailable_violation, etc.
            $table->string('severity')->default('medium'); // critical, high, medium, low
            $table->string('entity_type')->nullable(); // teacher, class_subject, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('data'); // All conflict details
            $table->timestamps();

            $table->index(['academic_term_id', 'type']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflicts');
    }
};
