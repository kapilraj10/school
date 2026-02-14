<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conflict extends Model
{
    protected $fillable = [
        'academic_term_id',
        'type',
        'severity',
        'entity_type',
        'entity_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public static function truncateForTerm(int $termId): void
    {
        static::where('academic_term_id', $termId)->delete();
    }

    public static function getGroupedByType(int $termId): array
    {
        $conflicts = static::where('academic_term_id', $termId)
            ->orderBy('severity')
            ->orderBy('created_at')
            ->get();

        $hardConflicts = $conflicts->whereIn('severity', ['critical', 'high']);
        $softConflicts = $conflicts->whereIn('severity', ['medium', 'low']);

        return [
            // Hard constraints
            'teacher_conflicts' => $conflicts->where('type', 'teacher_conflict'),
            'classroom_conflicts' => $conflicts->where('type', 'classroom_conflict'),
            'unavailable_violations' => $conflicts->where('type', 'unavailable_violation'),
            'overloaded_teachers' => $conflicts->where('type', 'overloaded_teacher'),
            'daily_overloads' => $conflicts->where('type', 'daily_overload'),
            'min_period_violations' => $conflicts->where('type', 'min_period_violation'),
            'max_period_violations' => $conflicts->where('type', 'max_period_violation'),
            'combined_period_violations' => $conflicts->where('type', 'combined_period_violation'),
            'empty_slot_violations' => $conflicts->where('type', 'empty_slot_violation'),
            'cocurricular_same_day_violations' => $conflicts->where('type', 'cocurricular_same_day'),
            'cocurricular_consecutive_violations' => $conflicts->where('type', 'cocurricular_consecutive'),
            'subject_daily_excess_violations' => $conflicts->where('type', 'subject_daily_excess'),
            'combined_grade_violations' => $conflicts->where('type', 'combined_grade_violation'),
            'physical_period_violations' => $conflicts->where('type', 'physical_period_violation'),
            'total_period_violations' => $conflicts->where('type', 'total_period_violation'),

            // Soft constraints
            'positional_consistency_violations' => $conflicts->where('type', 'positional_consistency'),
            'core_subject_consistency_violations' => $conflicts->where('type', 'core_subject_consistency'),
            'consecutive_heavy_violations' => $conflicts->where('type', 'consecutive_heavy'),
            'cocurricular_placement_violations' => $conflicts->where('type', 'cocurricular_placement'),
            'subject_daily_balance_violations' => $conflicts->where('type', 'subject_daily_balance'),

            'total_conflicts' => $conflicts->count(),
            'hard_conflicts' => $hardConflicts->count(),
            'soft_conflicts' => $softConflicts->count(),
        ];
    }
}
