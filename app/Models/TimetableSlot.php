<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    use HasFactory;

    public static $days = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];

    public static $periods = [
        1 => 'Period 1',
        2 => 'Period 2',
        3 => 'Period 3',
        4 => 'Period 4',
        5 => 'Period 5',
        6 => 'Period 6',
        7 => 'Period 7',
        8 => 'Period 8',
    ];

    protected $fillable = [
        'class_room_id',
        'subject_id',
        'teacher_id',
        'academic_term_id',
        'combined_period_id',
        'day',
        'period',
        'date',
        'start_time',
        'end_time',
        'type',
        'status',
        'is_locked',
        'is_combined',
        'notes',
    ];

    protected $casts = [
        'day' => 'integer',
        'period' => 'integer',
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_locked' => 'boolean',
        'is_combined' => 'boolean',
    ];

    /**
     * Get the class room for this slot
     */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    /**
     * Get the subject for this slot
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the teacher for this slot
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the academic term for this slot
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * Get the combined period for this slot
     */
    public function combinedPeriod(): BelongsTo
    {
        return $this->belongsTo(CombinedPeriod::class);
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute(): string
    {
        return self::$days[$this->day] ?? 'Unknown';
    }

    /**
     * Get period name
     */
    public function getPeriodNameAttribute(): string
    {
        return self::$periods[$this->period] ?? 'Unknown';
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'regular' => 'Regular Class',
            'break' => 'Break',
            'lunch' => 'Lunch',
            'assembly' => 'Assembly',
            'combined' => 'Combined Period',
            default => $this->type,
        };
    }

    /**
     * Check if slot is a break
     */
    public function isBreak(): bool
    {
        return in_array($this->type, ['break', 'lunch']);
    }

    /**
     * Scope for specific class room
     */
    public function scopeForClass($query, int $classRoomId)
    {
        return $query->where('class_room_id', $classRoomId);
    }

    /**
     * Scope for specific day
     */
    public function scopeForDay($query, int $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Scope for specific period
     */
    public function scopeForPeriod($query, int $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope for specific academic term
     */
    public function scopeForTerm($query, int $termId)
    {
        return $query->where('academic_term_id', $termId);
    }

    /**
     * Scope for regular classes only
     */
    public function scopeRegular($query)
    {
        return $query->where('type', 'regular');
    }

    /**
     * Scope for locked slots
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }
}
