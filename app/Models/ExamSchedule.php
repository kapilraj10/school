<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_term_id',
        'class_room_id',
        'title',
        'exam_type',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'is_school_wide',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'day_of_week' => 'integer',
        'is_school_wide' => 'boolean',
    ];

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }
}
