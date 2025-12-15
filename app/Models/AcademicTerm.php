<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTerm extends Model
{
    protected $fillable = [
        'name',
        'year',
        'term',
        'start_date',
        'end_date',
        'is_active',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the timetable slots for this academic term
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * Get the combined periods for this academic term
     */
    public function combinedPeriods(): HasMany
    {
        return $this->hasMany(CombinedPeriod::class);
    }

    /**
     * Get full term name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->year})";
    }

    /**
     * Get term display name
     */
    public function getTermDisplayAttribute(): string
    {
        return match($this->term) {
            '1' => 'First Term',
            '2' => 'Second Term',
            '3' => 'Third Term',
            default => "Term {$this->term}",
        };
    }

    /**
     * Scope for active academic term
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for current academic term (alias for active)
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific year
     */
    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope for specific status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
