<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Conflict;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConflictResolverService
{
    protected int $academicTermId;

    protected Collection $teachers;

    protected Collection $subjects;

    protected Collection $classRooms;

    protected Collection $timetableSlots;

    protected array $teacherSchedule = [];

    protected array $resolutionLog = [];

    protected int $maxPeriodsPerDay = 7;

    protected int $periodsPerDay = 8;

    protected int $daysPerWeek = 6;

    protected array $dayMap = [
        0 => 'Sun',
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
    ];

    protected array $dayNameToIndex = [
        'Sun' => 0,
        'Mon' => 1,
        'Tue' => 2,
        'Wed' => 3,
        'Thu' => 4,
        'Fri' => 5,
    ];

    public function __construct(int $academicTermId)
    {
        $this->academicTermId = $academicTermId;
        $this->loadData();
    }

    /**
     * Extract available days from availability_matrix
     */
    protected function getAvailableDaysFromMatrix(Teacher $teacher): array
    {
        if (empty($teacher->availability_matrix)) {
            return array_keys($this->dayMap);
        }

        $availableDays = [];
        foreach ($teacher->availability_matrix as $dayName => $periods) {
            if (! empty($periods) && isset($this->dayNameToIndex[$dayName])) {
                $availableDays[] = $this->dayNameToIndex[$dayName];
            }
        }

        return ! empty($availableDays) ? $availableDays : array_keys($this->dayMap);
    }

    /**
     * Extract available periods from availability_matrix for a specific day
     */
    protected function getAvailablePeriodsFromMatrix(Teacher $teacher, string $dayName): array
    {
        if (empty($teacher->availability_matrix)) {
            return range(1, $this->periodsPerDay);
        }

        return $teacher->availability_matrix[$dayName] ?? [];
    }

    /**
     * Get available day names from availability_matrix
     */
    protected function getAvailableDayNamesFromMatrix(Teacher $teacher): array
    {
        if (empty($teacher->availability_matrix)) {
            return array_values($this->dayMap);
        }

        $availableDayNames = [];
        foreach ($teacher->availability_matrix as $dayName => $periods) {
            if (! empty($periods)) {
                $availableDayNames[] = $dayName;
            }
        }

        return ! empty($availableDayNames) ? $availableDayNames : array_values($this->dayMap);
    }

    /**
     * Load necessary data for conflict resolution
     */
    protected function loadData(): void
    {
        $this->teachers = Teacher::where('status', 'active')->get()->keyBy('id');
        $this->subjects = Subject::where('status', 'active')->get()->keyBy('id');
        $this->classRooms = ClassRoom::all()->keyBy('id');
        $this->timetableSlots = TimetableSlot::where('academic_term_id', $this->academicTermId)
            ->with(['teacher', 'classRoom', 'subject'])
            ->get();

        foreach ($this->timetableSlots as $slot) {
            if ($slot->teacher_id) {
                $key = "{$slot->day}-{$slot->period}";
                if (! isset($this->teacherSchedule[$slot->teacher_id])) {
                    $this->teacherSchedule[$slot->teacher_id] = [];
                }
                if (! isset($this->teacherSchedule[$slot->teacher_id][$key])) {
                    $this->teacherSchedule[$slot->teacher_id][$key] = [];
                }
                $this->teacherSchedule[$slot->teacher_id][$key][] = $slot;
            }
        }
    }

    /**
     * Resolve all conflicts in the timetable
     */
    public function resolveAllConflicts(): array
    {
        DB::beginTransaction();

        try {
            $this->resolutionLog = [
                'started_at' => now()->toDateTimeString(),
                'initial_conflicts' => $this->getConflictCounts(),
                'actions' => [],
            ];

            // Step 1: Fix teacher double-booking conflicts
            $this->resolveTeacherConflicts();

            // Step 2: Fix unavailable period violations
            $this->resolveUnavailableViolations();

            // Step 3: Balance overloaded teachers
            $this->resolveOverloadedTeachers();

            // Step 4: Fix combined period violations
            $this->resolveCombinedPeriodViolations();

            // Refresh data
            $this->loadData();

            // Re-run validation by calling the conflict checker
            $this->revalidateConflicts();

            $this->resolutionLog['final_conflicts'] = $this->getConflictCounts();
            $this->resolutionLog['completed_at'] = now()->toDateTimeString();

            DB::commit();

            return $this->resolutionLog;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Conflict resolution failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Resolve teacher double-booking conflicts
     */
    protected function resolveTeacherConflicts(): void
    {
        $conflicts = Conflict::where('academic_term_id', $this->academicTermId)
            ->where('type', 'teacher_conflict')
            ->get();

        $resolved = 0;
        $failed = 0;

        foreach ($conflicts as $conflict) {
            $data = $conflict->data;
            $teacherId = $data['teacher_id'];
            $day = $data['day'];
            $period = $data['period'];

            $conflictingSlots = TimetableSlot::where('academic_term_id', $this->academicTermId)
                ->where('teacher_id', $teacherId)
                ->where('day', $day)
                ->where('period', $period)
                ->with(['teacher', 'classRoom'])
                ->get();

            if ($conflictingSlots->count() <= 1) {
                $conflict->delete();
                $resolved++;

                continue;
            }

            $slotsToReschedule = $conflictingSlots->slice(1);

            foreach ($slotsToReschedule as $slot) {
                if ($this->rescheduleSlot($slot)) {
                    $this->logAction('teacher_conflict_resolved', [
                        'teacher_id' => $teacherId,
                        'original_day' => $day,
                        'original_period' => $period,
                        'slot_id' => $slot->id,
                        'class_room' => $this->classRooms[$slot->class_room_id]->name ?? 'Unknown',
                    ]);
                    $resolved++;
                } else {
                    $failed++;
                }
            }

            $conflict->delete();
        }

        $this->logAction('teacher_conflicts_summary', [
            'resolved' => $resolved,
            'failed' => $failed,
        ]);
    }

    /**
     * Resolve unavailable period violations
     */
    protected function resolveUnavailableViolations(): void
    {
        $conflicts = Conflict::where('academic_term_id', $this->academicTermId)
            ->where('type', 'unavailable_violation')
            ->get();

        $resolved = 0;
        $failed = 0;

        foreach ($conflicts as $conflict) {
            $data = $conflict->data;
            $slotId = $data['id'] ?? null;
            $day = $data['day'] ?? null;
            $period = $data['period'] ?? null;

            if (! $slotId) {
                $conflict->delete();

                continue;
            }

            $slot = TimetableSlot::with(['teacher', 'classRoom'])->find($slotId);

            if (! $slot) {
                $conflict->delete();

                continue;
            }

            if ($this->rescheduleSlot($slot, true)) {
                $this->logAction('unavailable_violation_resolved', [
                    'teacher_id' => $slot->teacher_id,
                    'original_day' => $day,
                    'original_period' => $period,
                    'slot_id' => $slot->id,
                ]);
                $resolved++;
                $conflict->delete();
            } else {
                $failed++;
            }
        }

        $this->logAction('unavailable_violations_summary', [
            'resolved' => $resolved,
            'failed' => $failed,
        ]);
    }

    /**
     * Resolve overloaded teacher assignments
     */
    protected function resolveOverloadedTeachers(): void
    {
        $conflicts = Conflict::where('academic_term_id', $this->academicTermId)
            ->where('type', 'overloaded_teacher')
            ->get();

        $resolved = 0;
        $failed = 0;

        foreach ($conflicts as $conflict) {
            $data = $conflict->data;
            $teacherId = $data['id'] ?? null;
            $teacher = $this->teachers[$teacherId] ?? null;

            if (! $teacher) {
                $conflict->delete();

                continue;
            }

            // Get all slots for this teacher, ordered by day
            $teacherSlots = TimetableSlot::where('academic_term_id', $this->academicTermId)
                ->where('teacher_id', $teacherId)
                ->orderBy('day')
                ->orderBy('period')
                ->get();

            // Group by day and find overloaded days
            $slotsByDay = $teacherSlots->groupBy('day');

            foreach ($slotsByDay as $day => $daySlots) {
                if ($daySlots->count() > $this->maxPeriodsPerDay) {
                    // Try to move excess slots to other days
                    $slotsToMove = $daySlots->slice($this->maxPeriodsPerDay);

                    foreach ($slotsToMove as $slot) {
                        if ($this->rescheduleSlot($slot)) {
                            $this->logAction('overload_resolved', [
                                'teacher_id' => $teacherId,
                                'original_day' => $day,
                                'slot_id' => $slot->id,
                            ]);
                            $resolved++;
                        } else {
                            $failed++;
                        }
                    }
                }
            }

            $conflict->delete();
        }

        $this->logAction('overloaded_teachers_summary', [
            'resolved' => $resolved,
            'failed' => $failed,
        ]);
    }

    /**
     * Resolve combined period violations (non-adjacent periods)
     */
    protected function resolveCombinedPeriodViolations(): void
    {
        $conflicts = Conflict::where('academic_term_id', $this->academicTermId)
            ->where('type', 'combined_period_violation')
            ->get();

        $resolved = 0;
        $failed = 0;

        foreach ($conflicts as $conflict) {
            $data = $conflict->data;

            // Extract class and subject from the description
            // This is complex and requires parsing the stored data structure
            // For now, we'll mark these as requiring manual resolution
            $this->logAction('combined_period_requires_manual_fix', [
                'class_name' => $data['class_name'] ?? 'Unknown',
                'subject_name' => $data['subject_name'] ?? 'Unknown',
                'issue' => $data['issue'] ?? 'Unknown',
                'details' => $data['details'] ?? 'Unknown',
            ]);

            $failed++;
            // Don't delete these - they need manual attention
        }

        $this->logAction('combined_period_violations_summary', [
            'resolved' => $resolved,
            'requires_manual' => $failed,
        ]);
    }

    /**
     * Reschedule a slot to an available time
     */
    protected function rescheduleSlot(TimetableSlot $slot, bool $respectAvailability = false): bool
    {
        $teacher = $this->teachers[$slot->teacher_id] ?? null;
        $classRoom = $this->classRooms[$slot->class_room_id] ?? null;

        if (! $teacher || ! $classRoom) {
            return false;
        }

        // Extract available days from availability_matrix
        if ($respectAvailability) {
            $availableDays = $this->getAvailableDaysFromMatrix($teacher);
        } else {
            $availableDays = range(0, $this->daysPerWeek - 1);
        }

        // Try to find an alternative slot
        foreach ($availableDays as $day) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                if ($this->canScheduleSlot($slot, $day, $period)) {
                    $slot->day = $day;
                    $slot->period = $period;
                    $slot->save();

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a slot can be scheduled at a specific day/period
     */
    protected function canScheduleSlot(TimetableSlot $slot, int $day, int $period): bool
    {
        $teacher = $this->teachers[$slot->teacher_id] ?? null;

        if (! $teacher) {
            return false;
        }

        // Check teacher availability using availability_matrix
        $dayName = $this->dayMap[$day] ?? null;
        if (! $dayName) {
            return false;
        }

        $availableDayNames = $this->getAvailableDayNamesFromMatrix($teacher);
        if (! empty($availableDayNames) && ! in_array($dayName, $availableDayNames)) {
            return false;
        }

        $availablePeriods = $this->getAvailablePeriodsFromMatrix($teacher, $dayName);
        if (! empty($availablePeriods) && ! in_array($period, $availablePeriods)) {
            return false;
        }

        // Check if teacher is already scheduled at this time
        $existingSlot = TimetableSlot::where('academic_term_id', $this->academicTermId)
            ->where('teacher_id', $slot->teacher_id)
            ->where('day', $day)
            ->where('period', $period)
            ->where('id', '!=', $slot->id)
            ->exists();

        if ($existingSlot) {
            return false;
        }

        // Check if classroom is already occupied at this time
        $classroomOccupied = TimetableSlot::where('academic_term_id', $this->academicTermId)
            ->where('class_room_id', $slot->class_room_id)
            ->where('day', $day)
            ->where('period', $period)
            ->where('id', '!=', $slot->id)
            ->exists();

        if ($classroomOccupied) {
            return false;
        }

        // Check teacher daily workload
        $dailySlots = TimetableSlot::where('academic_term_id', $this->academicTermId)
            ->where('teacher_id', $slot->teacher_id)
            ->where('day', $day)
            ->where('id', '!=', $slot->id)
            ->count();

        if ($dailySlots >= $this->maxPeriodsPerDay) {
            return false;
        }

        return true;
    }

    /**
     * Find and schedule consecutive periods for combined subjects
     */
    protected function findAndScheduleConsecutivePeriods(
        int $classRoomId,
        int $subjectId,
        int $requiredPeriods,
        Collection $existingSlots
    ): bool {
        // Try each day
        for ($day = 0; $day < $this->daysPerWeek; $day++) {
            // Try to find consecutive periods
            for ($startPeriod = 1; $startPeriod <= $this->periodsPerDay - $requiredPeriods + 1; $startPeriod++) {
                $canSchedule = true;

                // Check if all required consecutive periods are available
                for ($i = 0; $i < $requiredPeriods; $i++) {
                    $period = $startPeriod + $i;
                    $slot = $existingSlots->get($i);

                    if (! $slot || ! $this->canScheduleSlot($slot, $day, $period)) {
                        $canSchedule = false;

                        break;
                    }
                }

                if ($canSchedule) {
                    // Schedule all slots consecutively
                    foreach ($existingSlots as $index => $slot) {
                        $slot->day = $day;
                        $slot->period = $startPeriod + $index;
                        $slot->save();
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get current conflict counts
     */
    protected function getConflictCounts(): array
    {
        return [
            'teacher_conflicts' => Conflict::where('academic_term_id', $this->academicTermId)
                ->where('type', 'teacher_conflict')
                ->count(),
            'unavailable_violations' => Conflict::where('academic_term_id', $this->academicTermId)
                ->where('type', 'unavailable_violation')
                ->count(),
            'overloaded_teachers' => Conflict::where('academic_term_id', $this->academicTermId)
                ->where('type', 'overloaded_teacher')
                ->count(),
            'combined_period_violations' => Conflict::where('academic_term_id', $this->academicTermId)
                ->where('type', 'combined_period_violation')
                ->count(),
            'total' => Conflict::where('academic_term_id', $this->academicTermId)->count(),
        ];
    }

    /**
     * Log an action
     */
    protected function logAction(string $action, array $details): void
    {
        $this->resolutionLog['actions'][] = [
            'action' => $action,
            'details' => $details,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get resolution log
     */
    public function getResolutionLog(): array
    {
        return $this->resolutionLog;
    }

    /**
     * Revalidate conflicts by running the same checks as the conflict checker
     */
    protected function revalidateConflicts(): void
    {
        // Truncate old conflicts
        Conflict::truncateForTerm($this->academicTermId);

        // Check teacher conflicts
        $teacherConflicts = $this->findTeacherConflicts();
        foreach ($teacherConflicts as $conflict) {
            Conflict::create([
                'academic_term_id' => $this->academicTermId,
                'type' => 'teacher_conflict',
                'severity' => 'critical',
                'entity_type' => 'teacher',
                'entity_id' => $conflict['teacher_id'],
                'data' => $conflict,
            ]);
        }

        // Check unavailable violations
        $unavailableViolations = $this->findUnavailableViolations();
        foreach ($unavailableViolations as $violation) {
            Conflict::create([
                'academic_term_id' => $this->academicTermId,
                'type' => 'unavailable_violation',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => null,
                'data' => $violation,
            ]);
        }

        // Check overloaded teachers
        $overloadedTeachers = $this->findOverloadedTeachers();
        foreach ($overloadedTeachers as $teacher) {
            Conflict::create([
                'academic_term_id' => $this->academicTermId,
                'type' => 'overloaded_teacher',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => $teacher['id'],
                'data' => $teacher,
            ]);
        }
    }

    /**
     * Find teacher conflicts (double bookings)
     */
    protected function findTeacherConflicts(): array
    {
        $conflicts = [];
        $slots = DB::table('timetable_slots')
            ->where('academic_term_id', $this->academicTermId)
            ->whereNotNull('teacher_id')
            ->get();

        $grouped = $slots->groupBy(function ($slot) {
            return $slot->teacher_id.'-'.$slot->day.'-'.$slot->period;
        });

        foreach ($grouped as $key => $group) {
            if ($group->count() > 1) {
                [$teacherId, $day, $period] = explode('-', $key);
                $firstSlot = $group->first();
                $secondSlot = $group->get(1);

                $teacher = $this->teachers[$teacherId] ?? null;
                $conflicts[] = [
                    'teacher_id' => (int) $teacherId,
                    'teacher_name' => $teacher?->name ?? "T{$teacherId}",
                    'day' => (int) $day,
                    'period' => (int) $period,
                    'subject1' => $this->subjects[$firstSlot->subject_id]?->name ?? 'Unknown',
                    'subject2' => $this->subjects[$secondSlot->subject_id]?->name ?? 'Unknown',
                    'class1' => $this->classRooms[$firstSlot->class_room_id]?->name ?? 'Unknown',
                    'class2' => $this->classRooms[$secondSlot->class_room_id]?->name ?? 'Unknown',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Find unavailable violations
     */
    protected function findUnavailableViolations(): array
    {
        $violations = [];

        foreach ($this->timetableSlots as $slot) {
            if (! $slot->teacher_id) {
                continue;
            }

            $teacher = $this->teachers[$slot->teacher_id] ?? null;
            if (! $teacher) {
                continue;
            }

            $dayName = $this->dayMap[$slot->day] ?? null;
            if (! $dayName) {
                continue;
            }

            $availableDayNames = $this->getAvailableDayNamesFromMatrix($teacher);
            $availablePeriods = $this->getAvailablePeriodsFromMatrix($teacher, $dayName);

            $isDayUnavailable = ! empty($availableDayNames) && ! in_array($dayName, $availableDayNames);
            $isPeriodUnavailable = ! empty($availablePeriods) && ! in_array($slot->period, $availablePeriods);

            if ($isDayUnavailable || $isPeriodUnavailable) {
                $violations[] = [
                    'id' => $slot->id,
                    'day' => $slot->day,
                    'period' => $slot->period,
                    'teacher_name' => $teacher->name,
                    'available_days' => json_encode($availableDayNames),
                    'available_periods' => json_encode($availablePeriods),
                ];
            }
        }

        return $violations;
    }

    /**
     * Find overloaded teachers
     */
    protected function findOverloadedTeachers(): array
    {
        $overloaded = [];

        foreach ($this->teachers as $teacher) {
            $assignedPeriods = $this->timetableSlots->where('teacher_id', $teacher->id)->count();

            if ($assignedPeriods > $teacher->max_periods_per_week) {
                $overloaded[] = [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'max_periods_per_week' => $teacher->max_periods_per_week,
                    'assigned_periods' => $assignedPeriods,
                ];
            }
        }

        return $overloaded;
    }
}
