<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    protected $fillable = [
        'name',
        'section',
        'weekly_periods',
        'total_subjects',
        'status',
    ];

    protected $casts = [
        'weekly_periods' => 'integer',
        'total_subjects' => 'integer',
    ];

    /**
     * Get the timetable slots for this class
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * Get full class name (e.g., "Class 1 - A")
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} - {$this->section}";
    }

    /**
     * Scope for active classes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
