<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    protected $fillable = [
        'name',
        'section',
        'level',
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
     * Get level display name
     */
    public function getLevelDisplayAttribute(): string
    {
        return match($this->level) {
            'basic_1_3' => 'Basic (1-3)',
            'basic_4_8' => 'Basic (4-8)',
            'secondary_9_10' => 'Secondary (9-10)',
            default => $this->level,
        };
    }

    /**
     * Scope for active classes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for specific level
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }
}
