<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'phone',
        'subject_ids',
        'max_periods_per_day',
        'max_periods_per_week',
        'available_days',
        'available_periods',
        'status',
    ];

    protected $casts = [
        'subject_ids' => 'array',
        'max_periods_per_day' => 'integer',
        'max_periods_per_week' => 'integer',
        'available_days' => 'array',
        'available_periods' => 'array',
    ];

    /**
     * Get the timetable slots for this teacher
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * Get the combined periods for this teacher
     */
    public function combinedPeriods(): HasMany
    {
        return $this->hasMany(CombinedPeriod::class);
    }

    /**
     * Get subjects this teacher can teach
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects');
    }

    /**
     * Get subjects by IDs
     */
    public function getSubjectsAttribute()
    {
        if (empty($this->subject_ids)) {
            return collect([]);
        }
        return Subject::whereIn('id', $this->subject_ids)->get();
    }

    /**
     * Check if teacher can teach a specific subject
     */
    public function canTeachSubject(int $subjectId): bool
    {
        return in_array($subjectId, $this->subject_ids ?? []);
    }

    /**
     * Check if teacher is available at a specific day and period
     */
    public function isAvailable(int $day, int $period): bool
    {
        if (empty($this->unavailable_periods)) {
            return true;
        }

        foreach ($this->unavailable_periods as $unavailable) {
            if ($unavailable['day'] == $day && $unavailable['period'] == $period) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope for active teachers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
