<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_term_id',
        'class_room_id',
        'name',
        'event_type',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'is_school_wide',
        'blocks_timetable',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'day_of_week' => 'integer',
        'is_school_wide' => 'boolean',
        'blocks_timetable' => 'boolean',
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
