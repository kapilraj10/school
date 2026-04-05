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
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->boolean('is_temporary')
                ->default(false)
                ->after('is_combined');

            $table->foreignId('original_subject_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('subjects')
                ->nullOnDelete();

            $table->foreignId('original_teacher_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained('teachers')
                ->nullOnDelete();

            $table->string('temporary_type')
                ->nullable()
                ->after('type');

            $table->text('temporary_reason')
                ->nullable()
                ->after('notes');

            $table->date('temporary_effective_from')
                ->nullable()
                ->after('date');

            $table->date('temporary_effective_until')
                ->nullable()
                ->after('temporary_effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->dropForeign(['original_subject_id']);
            $table->dropForeign(['original_teacher_id']);

            $table->dropColumn([
                'is_temporary',
                'original_subject_id',
                'original_teacher_id',
                'temporary_type',
                'temporary_reason',
                'temporary_effective_from',
                'temporary_effective_until',
            ]);
        });
    }
};
