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

        return [
            'teacher_conflicts' => $conflicts->where('type', 'teacher_conflict'),
            'unavailable_violations' => $conflicts->where('type', 'unavailable_violation'),
            'overloaded_teachers' => $conflicts->where('type', 'overloaded_teacher'),
            'min_period_violations' => $conflicts->where('type', 'min_period_violation'),
            'max_period_violations' => $conflicts->where('type', 'max_period_violation'),
            'combined_period_violations' => $conflicts->where('type', 'combined_period_violation'),
            'total_conflicts' => $conflicts->count(),
        ];
    }
}
