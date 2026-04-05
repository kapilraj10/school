<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'phone',
        'subject_ids',
        'class_room_ids',
        'max_periods_per_day',
        'max_periods_per_week',
        'availability_matrix',
        'status',
    ];

    protected $casts = [
        'subject_ids' => 'array',
        'class_room_ids' => 'array',
        'max_periods_per_day' => 'integer',
        'max_periods_per_week' => 'integer',
        'availability_matrix' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (Teacher $teacher): void {
            $teacher->syncSubjectRelationships();
        });

        static::deleted(function (Teacher $teacher): void {
            $teacher->subjects()->detach();
        });
    }

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
        return $this->belongsToMany(Subject::class, 'teacher_subject');
    }

    /**
     * Get classes this teacher is assigned to
     */
    public function classRooms(): Collection
    {
        if (empty($this->class_room_ids)) {
            return collect([]);
        }

        return ClassRoom::whereIn('id', $this->class_room_ids)->get();
    }

    /**
     * Get subjects by IDs
     */
    public function getSubjectsAttribute(): Collection
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
        return in_array($subjectId, array_map('intval', $this->subject_ids ?? []), true);
    }

    /**
     * Check if teacher is assigned to a specific class
     */
    public function canTeachClass(int $classRoomId): bool
    {
        if (empty($this->class_room_ids)) {
            return true;
        }

        return in_array($classRoomId, array_map('intval', $this->class_room_ids), true);
    }

    /**
     * Check if teacher is available at a specific day and period
     */
    public function isAvailable(int $day, int $period): bool
    {
        $days = TimetableSlot::getDays();
        $dayName = $days[$day] ?? null;

        if ($dayName === null) {
            return true;
        }

        $dayMap = [
            'Sunday' => 'Sun',
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
        ];

        $dayShort = $dayMap[$dayName] ?? $dayName;

        return $this->isAvailableAt($dayShort, $period);
    }

    /**
     * Get available days derived from availability matrix
     */
    public function getAvailableDays(): array
    {
        if (empty($this->availability_matrix)) {
            return [];
        }

        $availableDays = [];
        foreach ($this->availability_matrix as $dayShort => $periods) {
            if (! empty(array_filter($periods))) {
                $availableDays[] = $dayShort;
            }
        }

        return $availableDays;
    }

    /**
     * Get available periods derived from availability matrix
     */
    public function getAvailablePeriods(): array
    {
        if (empty($this->availability_matrix)) {
            return [];
        }

        $availablePeriods = [];
        foreach ($this->availability_matrix as $dayShort => $periods) {
            foreach ($periods as $period => $isAvailable) {
                if ($isAvailable && ! in_array($period, $availablePeriods)) {
                    $availablePeriods[] = $period;
                }
            }
        }

        sort($availablePeriods);

        return $availablePeriods;
    }

    /**
     * Check if teacher is available at a specific day and period using availability matrix
     */
    public function isAvailableAt(string $dayShort, int $period): bool
    {
        if (empty($this->availability_matrix)) {
            return true;
        }

        return (bool) ($this->availability_matrix[$dayShort][$period] ?? false);
    }

    /**
     * Scope for active teachers
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function syncSubjectRelationships(): void
    {
        $subjectIds = collect($this->subject_ids ?? [])
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->subjects()->sync($subjectIds);
    }
}
