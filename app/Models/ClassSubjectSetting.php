<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubjectSetting extends Model
{
    protected $fillable = [
        'class_room_id',
        'subject_id',
        'room_id',
        'min_periods_per_week',
        'max_periods_per_week',
        'weekly_periods',
        'single_combined',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'min_periods_per_week' => 'integer',
        'max_periods_per_week' => 'integer',
        'weekly_periods' => 'integer',
        'single_combined' => 'string',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the class room
     */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    /**
     * Get the subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the assigned special room/lab for this class-subject setting
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific class
     */
    public function scopeForClass($query, int $classRoomId)
    {
        return $query->where('class_room_id', $classRoomId);
    }

    /**
     * Get settings for a class with subject info
     */
    public static function getForClass(int $classRoomId): \Illuminate\Database\Eloquent\Collection
    {
        return static::with('subject')
            ->where('class_room_id', $classRoomId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Sync subjects for a class based on class range
     */
    public static function syncSubjectsForClass(ClassRoom $classRoom): void
    {
        $subjects = Subject::where('class_room_id', $classRoom->id)
            ->where('status', 'active')
            ->get();

        foreach ($subjects as $subject) {
            static::updateOrCreate(
                [
                    'class_room_id' => $classRoom->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'min_periods_per_week' => 1,
                    'max_periods_per_week' => 6,
                    'weekly_periods' => 4,
                    'single_combined' => 'single',
                    'is_active' => true,
                    'priority' => 5,
                ]
            );
        }
    }
}
