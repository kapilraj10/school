<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'class_room_id',
        'type',
        'level',
        'status',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * Get the combined periods for this subject
     */
    public function combinedPeriods(): HasMany
    {
        return $this->hasMany(CombinedPeriod::class);
    }

    /**
     * Get the class subject settings for this subject
     */
    public function classSubjectSettings(): HasMany
    {
        return $this->hasMany(ClassSubjectSetting::class);
    }

    /**
     * Get assigned room/lab through class subject setting
     */
    public function assignedRoom(): HasOneThrough
    {
        return $this->hasOneThrough(
            Room::class,
            ClassSubjectSetting::class,
            'subject_id',
            'id',
            'id',
            'room_id'
        );
    }

    /**
     * Get teachers who can teach this subject
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject');
    }

    /**
     * Get teachers by checking subject_ids JSON column
     */
    public function getAvailableTeachers()
    {
        return Teacher::active()
            ->whereJsonContains('subject_ids', $this->id)
            ->get();
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'core' => 'Core Subject',
            'elective' => 'Elective',
            'co_curricular' => 'Co-Curricular',
            default => $this->type,
        };
    }

    /**
     * Scope for active subjects
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

    /**
     * Scope for specific type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific level (forLevel alias for compatibility)
     */
    public function scopeForLevel($query, ?string $level)
    {
        if ($level === null) {
            return $query;
        }

        return $query->where('level', $level);
    }
}
