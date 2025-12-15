<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CombinedPeriod extends Model
{
    protected $fillable = [
        'name',
        'subject_id',
        'teacher_id',
        'class_room_ids',
        'day',
        'period',
        'frequency',
        'academic_term_id',
    ];

    protected $casts = [
        'class_room_ids' => 'array',
        'day' => 'integer',
        'period' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function getClassRooms()
    {
        return ClassRoom::whereIn('id', $this->class_room_ids ?? [])->get();
    }

    public function getDayNameAttribute(): string
    {
        return TimetableSlot::$days[$this->day] ?? 'Unknown';
    }
}
