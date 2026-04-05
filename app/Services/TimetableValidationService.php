<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;

class TimetableValidationService
{
    /** Maximum periods a teacher may teach across all classes in a single day (DB: max_teacher_periods_per_day) */
    private int $maxTeacherPeriodsPerDay;

    /** Maximum periods a single subject may appear in one day (DB: max_same_subject_per_day) */
    private int $maxSubjectPeriodsPerDay;

    /** Number of periods per school day (DB: periods_per_day) */
    private int $periodsPerDay;

    /** Core subjects list (DB: core_subjects) */
    private array $coreSubjects;

    /** Mentally heavy subjects list (DB: heavy_subjects) */
    private array $heavySubjects;

    /** Physical activity subject keywords that cannot be placed in period 5 (DB: physical_subjects) */
    private array $physicalSubjects;

    /** Preferred period slots for co-curricular activities (DB: preferred_eca_periods) */
    private array $preferredCoCurricularPeriods;

    private array $errors = [];

    private array $warnings = [];

    /** Active school-day map: [dayOfWeek => dayName], populated from TimetableSetting */
    private array $dayNames = [];

    /** @var array<string, int|null> */
    private array $subjectRoomCache = [];

    public function __construct()
    {
        // Load all dynamic settings from the database (with sensible defaults)
        $this->maxTeacherPeriodsPerDay = (int) TimetableSetting::get('max_teacher_periods_per_day', 7);
        $this->maxSubjectPeriodsPerDay = (int) TimetableSetting::get('max_same_subject_per_day', 2);
        $this->periodsPerDay = (int) TimetableSetting::get('periods_per_day', 8);
        $this->coreSubjects = TimetableSetting::get('core_subjects', ['English', 'Maths', 'Science', 'Nepali', 'Math']);
        $this->heavySubjects = TimetableSetting::get('heavy_subjects', ['Maths', 'Math', 'Science', 'English', 'Nepali', 'Social']);
        $this->physicalSubjects = TimetableSetting::get('physical_subjects', ['sport', 'taekwondo', 'dance', 'physical education', 'yoga']);
        $this->preferredCoCurricularPeriods = TimetableSetting::get('preferred_eca_periods', [4, 5, 6, 7, 8]);

        $allDayMap = [
            'Sunday' => 0,
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
        ];

        $schoolDayNames = TimetableSetting::get(
            'school_days',
            ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
        );

        $this->dayNames = [];
        foreach ($schoolDayNames as $dayName) {
            $dayNum = $allDayMap[$dayName] ?? null;
            if ($dayNum !== null) {
                $this->dayNames[$dayNum] = $dayName;
            }
        }
    }

    /**
     * Validate a single slot assignment before saving
     *
     * @param  int  $classRoomId  The class room ID
     * @param  int  $termId  The academic term ID
     * @param  int  $subjectId  The subject ID to assign
     * @param  int|null  $teacherId  The teacher ID to assign
     * @param  int  $day  Day of week (1-6)
     * @param  int  $period  Period number (1-8)
     * @param  array  $pendingSlots  Unsaved changes from the designer (keyed by "date_period")
     * @return array ['errors' => [], 'warnings' => []]
     */
    public function validateSlotAssignment(
        int $classRoomId,
        int $termId,
        int $subjectId,
        ?int $teacherId,
        int $day,
        int $period,
        array $pendingSlots = []
    ): array {
        $this->errors = [];
        $this->warnings = [];

        // Load saved slots from DB then overlay any unsaved (pending) changes
        $existingSlots = $this->loadExistingSlotsAsArray($classRoomId, $termId);
        $existingSlots = $this->mergePendingSlots($existingSlots, $pendingSlots, $classRoomId, $day, $period);

        // Load subject and teacher details
        $subject = Subject::find($subjectId);
        $teacher = $teacherId ? Teacher::find($teacherId) : null;

        if (! $subject) {
            $this->errors[] = [
                'type' => 'invalid_subject',
                'message' => 'Subject not found',
            ];

            return ['errors' => $this->errors, 'warnings' => $this->warnings];
        }

        // Validate this specific slot assignment
        $this->validateSingleSlot($existingSlots, $subject, $teacher, $day, $period, $classRoomId, $termId);

        return [
            'errors' => $this->withSuggestions($this->errors),
            'warnings' => $this->withSuggestions($this->warnings),
        ];
    }

    /**
     * Validate a complete timetable for a class and term
     *
     * @param  int  $classRoomId  The class room ID
     * @param  int  $termId  The academic term ID
     * @return array ['errors' => [], 'warnings' => [], 'has_errors' => bool, 'has_warnings' => bool]
     */
    public function validateCompleteTimetable(int $classRoomId, int $termId): array
    {
        $this->errors = [];
        $this->warnings = [];

        // Load all slots for this class and term
        $slots = $this->loadExistingSlotsAsArray($classRoomId, $termId);

        // Run all validations
        $this->validateHardConstraints($slots, $classRoomId, $termId);
        $this->validateSoftConstraints($slots, $classRoomId);

        $errors = $this->withSuggestions($this->errors);
        $warnings = $this->withSuggestions($this->warnings);

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'has_errors' => count($errors) > 0,
            'has_warnings' => count($warnings) > 0,
        ];
    }

    /**
     * Main validation entry point (legacy method for backward compatibility)
     *
     * @param  array  $slots  Array of timetable slots
     * @param  int  $classRoomId  The class room ID
     * @param  int  $termId  The academic term ID
     * @return array ['errors' => [], 'warnings' => [], 'has_errors' => bool, 'has_warnings' => bool]
     */
    public function validate(array $slots, int $classRoomId, int $termId): array
    {
        $this->errors = [];
        $this->warnings = [];

        // Run all validations
        $this->validateHardConstraints($slots, $classRoomId, $termId);
        $this->validateSoftConstraints($slots, $classRoomId);

        $errors = $this->withSuggestions($this->errors);
        $warnings = $this->withSuggestions($this->warnings);

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'has_errors' => count($errors) > 0,
            'has_warnings' => count($warnings) > 0,
        ];
    }

    /**
     * Merge pending (unsaved) designer changes into a slots array.
     * Additions overwrite, deletions remove. The slot being validated (day+period)
     * is deliberately excluded so it does not count as "already existing" when we
     * check limits for the very slot we are about to assign.
     *
     * @param  array  $existingSlots  Slots loaded from DB [day][period]
     * @param  array  $pendingSlots  Unsaved changes keyed by "date_period"
     * @param  int  $classRoomId  Only include pending slots for this class
     * @param  int  $currentDay  Day being validated (exclude from existing)
     * @param  int  $currentPeriod  Period being validated (exclude from existing)
     */
    private function mergePendingSlots(
        array $existingSlots,
        array $pendingSlots,
        int $classRoomId,
        int $currentDay,
        int $currentPeriod
    ): array {
        // Remove the slot being validated from existing so we don't double-count
        unset($existingSlots[$currentDay][$currentPeriod]);

        foreach ($pendingSlots as $change) {
            // Skip changes for other classes
            if (isset($change['class_room_id']) && (int) $change['class_room_id'] !== $classRoomId) {
                continue;
            }

            $changeDay = (int) ($change['day'] ?? -1);
            $changePeriod = (int) ($change['period'] ?? -1);

            // Skip the slot currently being evaluated
            if ($changeDay === $currentDay && $changePeriod === $currentPeriod) {
                continue;
            }

            if (isset($change['deleted']) && $change['deleted']) {
                // Remove deleted slots so they don't block the new assignment
                unset($existingSlots[$changeDay][$changePeriod]);
            } elseif (isset($change['subject_id'])) {
                // Look up subject_type if not stored in the pending entry
                $subjectType = $change['subject_type']
                    ?? Subject::find($change['subject_id'])?->type;

                $existingSlots[$changeDay][$changePeriod] = [
                    'subject_id' => (int) $change['subject_id'],
                    'subject_name' => $change['subject_name'] ?? 'Unknown',
                    'subject_type' => $subjectType,
                    'teacher_id' => $change['teacher_id'] ?? null,
                    'teacher_name' => $change['teacher_name'] ?? 'Unknown',
                ];
            }
        }

        return $existingSlots;
    }

    /**
     * Load existing timetable slots as array structure
     *
     * @param  int  $classRoomId  The class room ID
     * @param  int  $termId  The academic term ID
     * @return array Nested array [day][period] => slot data
     */
    private function loadExistingSlotsAsArray(int $classRoomId, int $termId): array
    {
        $slots = [];

        $timetableSlots = TimetableSlot::where('class_room_id', $classRoomId)
            ->where('academic_term_id', $termId)
            ->with(['subject', 'teacher'])
            ->get();

        foreach ($timetableSlots as $slot) {
            if (! isset($slots[$slot->day])) {
                $slots[$slot->day] = [];
            }

            $slots[$slot->day][$slot->period] = [
                'id' => $slot->id,
                'subject_id' => $slot->subject_id,
                'subject_name' => $slot->subject?->name ?? 'Unknown',
                'subject_type' => $slot->subject?->type ?? null,
                'teacher_id' => $slot->teacher_id,
                'teacher_name' => $slot->teacher?->name ?? 'Unknown',
                'room_id' => $this->getAssignedRoomId($classRoomId, (int) $slot->subject_id),
            ];
        }

        return $slots;
    }

    /**
     * Validate a single slot assignment against existing timetable
     *
     * @param  array  $existingSlots  Current timetable slots
     * @param  Subject  $subject  Subject to assign
     * @param  Teacher|null  $teacher  Teacher to assign
     * @param  int  $day  Day of week (1-6)
     * @param  int  $period  Period number (1-8)
     * @param  int  $classRoomId  Class room ID
     * @param  int  $termId  Academic term ID
     */
    private function validateSingleSlot(
        array $existingSlots,
        Subject $subject,
        ?Teacher $teacher,
        int $day,
        int $period,
        int $classRoomId,
        int $termId
    ): void {
        $dayName = $this->dayNames[$day] ?? "Day {$day}";

        // 1. Check subject daily limit (max 2 periods per day for this subject)
        $subjectCountToday = 0;
        foreach ($existingSlots[$day] ?? [] as $p => $slot) {
            if ($slot['subject_id'] === $subject->id && $p !== $period) {
                $subjectCountToday++;
            }
        }

        if ($subjectCountToday >= $this->maxSubjectPeriodsPerDay) {
            $this->errors[] = [
                'type' => 'subject_daily_limit',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    '%s already appears %d times on %s. Maximum %d periods per day allowed.',
                    $subject->name,
                    $subjectCountToday,
                    $dayName,
                    $this->maxSubjectPeriodsPerDay
                ),
            ];
        }

        // 2. Check co-curricular rules
        if ($subject->type === 'co_curricular') {
            $this->validateCoCurricularSlot($existingSlots, $subject, $day, $period, $dayName);
        }

        // 3. Check physical subject restriction: no physical activity in period 5
        if ($period === 5 && $this->isPhysicalSubject($subject->name)) {
            $this->errors[] = [
                'type' => 'physical_fifth_period',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    '%s (physical activity) cannot be scheduled in period 5 on %s.',
                    $subject->name,
                    $dayName
                ),
            ];
        }

        // 4. Check teacher constraints
        if ($teacher) {
            $this->validateTeacherSlot($teacher, $day, $period, $dayName, $termId, $classRoomId);
        }

        // 5. Check room/lab conflict constraints
        $this->validateRoomSlot($classRoomId, $subject->id, $day, $period, $termId, $dayName);

        // 6. Check weekly requirements (warning if needed)
        $this->checkWeeklySingleSlot($existingSlots, $subject, $classRoomId);

        // 7. Soft validation - check if subject already appears today
        if ($subjectCountToday > 0) {
            $this->warnings[] = [
                'type' => 'daily_repetition',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    '%s already appears on %s. Consider spreading across different days.',
                    $subject->name,
                    $dayName
                ),
                'severity' => 'low',
            ];
        }
    }

    /**
     * Validate co-curricular slot assignment
     *
     * @param  array  $existingSlots  Current timetable slots
     * @param  Subject  $subject  Co-curricular subject to assign
     * @param  int  $day  Day of week
     * @param  int  $period  Period number
     * @param  string  $dayName  Day name for messages
     */
    private function validateCoCurricularSlot(
        array $existingSlots,
        Subject $subject,
        int $day,
        int $period,
        string $dayName
    ): void {
        $coCurricularSubjectIds = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        $totalCoCurricularToday = [];
        $thisSubjectPeriods = [];

        foreach ($existingSlots[$day] ?? [] as $p => $slot) {
            if (in_array($slot['subject_id'], $coCurricularSubjectIds)) {
                $totalCoCurricularToday[] = $p;

                if ($slot['subject_id'] === $subject->id) {
                    $thisSubjectPeriods[] = $p;
                }
            }
        }

        // Rule 1: Max 2 co-curricular periods per day (any subject combination)
        if (count($totalCoCurricularToday) >= 2) {
            $this->errors[] = [
                'type' => 'cocurricular_period_limit',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Cannot assign %s on %s — already 2 co-curricular periods scheduled (maximum per day).',
                    $subject->name,
                    $dayName
                ),
            ];

            return;
        }

        // Rule 2: If the same subject already has a period today, the new period must be consecutive
        if (count($thisSubjectPeriods) === 1) {
            $existingPeriod = $thisSubjectPeriods[0];

            if (abs($period - $existingPeriod) !== 1) {
                $this->errors[] = [
                    'type' => 'cocurricular_not_consecutive',
                    'day' => $day,
                    'day_name' => $dayName,
                    'period' => $period,
                    'message' => sprintf(
                        '%s period %d must be consecutive to its existing period %d on %s.',
                        $subject->name,
                        $period,
                        $existingPeriod,
                        $dayName
                    ),
                ];
            }
        }

        // Soft warning: co-curricular should be in middle/later periods
        if (! in_array($period, $this->preferredCoCurricularPeriods)) {
            $this->warnings[] = [
                'type' => 'cocurricular_early_placement',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Co-curricular %s is in period %d. Consider placing in middle or later periods (4-8).',
                    $subject->name,
                    $period
                ),
                'severity' => 'low',
            ];
        }
    }

    /**
     * Validate teacher slot assignment
     *
     * @param  Teacher  $teacher  Teacher to assign
     * @param  int  $day  Day of week
     * @param  int  $period  Period number
     * @param  string  $dayName  Day name for messages
     * @param  int  $termId  Academic term ID
     * @param  int  $classRoomId  Class room ID
     */
    private function validateTeacherSlot(
        Teacher $teacher,
        int $day,
        int $period,
        string $dayName,
        int $termId,
        int $classRoomId
    ): void {
        if (! $teacher->isAvailable($day, $period)) {
            $this->errors[] = [
                'type' => 'teacher_unavailable',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Teacher %s is marked unavailable for %s Period %d.',
                    $teacher->name,
                    $dayName,
                    $period
                ),
            ];
        }

        if (! $teacher->canTeachClass($classRoomId)) {
            $this->errors[] = [
                'type' => 'teacher_class_assignment',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Teacher %s is not assigned to teach this class.',
                    $teacher->name
                ),
            ];
        }

        $conflict = TimetableSlot::where('teacher_id', $teacher->id)
            ->where('academic_term_id', $termId)
            ->where('day', $day)
            ->where('period', $period)
            ->where('class_room_id', '!=', $classRoomId)
            ->with(['classRoom', 'subject'])
            ->first();

        if ($conflict) {
            $conflictClass = $conflict->classRoom ? "{$conflict->classRoom->name} {$conflict->classRoom->section}" : 'another class';
            $conflictSubject = $conflict->subject ? " ({$conflict->subject->name})" : '';

            $this->errors[] = [
                'type' => 'teacher_conflict',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Teacher %s is already teaching %s%s at %s Period %d',
                    $teacher->name,
                    $conflictClass,
                    $conflictSubject,
                    $dayName,
                    $period
                ),
            ];
        }

        // Check daily workload across ALL classes (max 7 periods per day)
        $periodsToday = TimetableSlot::where('teacher_id', $teacher->id)
            ->where('academic_term_id', $termId)
            ->where('day', $day)
            ->count();

        if ($periodsToday >= $this->maxTeacherPeriodsPerDay) {
            $this->errors[] = [
                'type' => 'teacher_workload',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Teacher %s already has %d periods on %s. Maximum %d periods per day allowed.',
                    $teacher->name,
                    $periodsToday,
                    $dayName,
                    $this->maxTeacherPeriodsPerDay
                ),
            ];
        }
    }

    /**
     * Check weekly requirements for single slot assignment
     *
     * @param  array  $existingSlots  Current timetable slots
     * @param  Subject  $subject  Subject to check
     * @param  int  $classRoomId  Class room ID
     */
    private function checkWeeklySingleSlot(array $existingSlots, Subject $subject, int $classRoomId): void
    {
        // Count existing periods for this subject
        $currentCount = 0;
        foreach ($existingSlots as $day => $periods) {
            foreach ($periods as $period => $slot) {
                if ($slot['subject_id'] === $subject->id) {
                    $currentCount++;
                }
            }
        }

        // Get requirements from ClassSubjectSetting
        $setting = ClassSubjectSetting::where('class_room_id', $classRoomId)
            ->where('subject_id', $subject->id)
            ->where('is_active', true)
            ->first();

        if ($setting) {
            $minRequired = $setting->min_periods_per_week ?? 0;
            $maxAllowed = $setting->max_periods_per_week ?? $setting->weekly_periods ?? 999;

            // Adding one more period
            $newCount = $currentCount + 1;

            if ($newCount > $maxAllowed) {
                $this->errors[] = [
                    'type' => 'weekly_maximum',
                    'message' => sprintf(
                        '%s would have %d periods (Max allowed: %d)',
                        $subject->name,
                        $newCount,
                        $maxAllowed
                    ),
                ];
            }

            // Warning if still under minimum
            if ($newCount < $minRequired) {
                $this->warnings[] = [
                    'type' => 'weekly_minimum_progress',
                    'message' => sprintf(
                        '%s will have %d periods (Min required: %d). Add %d more.',
                        $subject->name,
                        $newCount,
                        $minRequired,
                        $minRequired - $newCount
                    ),
                    'severity' => 'info',
                ];
            }
        }
    }

    /**
     * Validate all hard constraints (must pass to save)
     */
    private function validateHardConstraints(array $slots, int $classRoomId, int $termId): void
    {
        // 1. Check timetable structure (empty slots warning)
        $this->checkTimetableStructure($slots);

        // 2. Check subject daily limits
        $this->checkSubjectDailyLimit($slots);

        // 3. Check weekly requirements
        $this->checkWeeklyRequirements($slots, $classRoomId);

        // 4. Check co-curricular rules (most complex)
        $this->checkCoCurricularRules($slots);

        // 5. Check teacher constraints
        $this->checkTeacherConstraints($slots, $termId, $classRoomId);

        // 6. Check room/lab constraints
        $this->checkRoomConstraints($slots, $termId, $classRoomId);

        // 7. Check subject allocations are balanced across the week
        $this->checkSubjectWeeklyBalance($slots);

        // 8. Check combined subjects only span sections of the same grade
        $this->checkCombinedSubjectRules($classRoomId, $termId);

        // 9. Check no physical activity subject in period 5
        $this->checkPhysicalSubjectFifthPeriod($slots);
    }

    /**
     * HARD: Validate subject room assignment for a single slot.
     */
    private function validateRoomSlot(
        int $classRoomId,
        int $subjectId,
        int $day,
        int $period,
        int $termId,
        string $dayName
    ): void {
        $roomId = $this->getAssignedRoomId($classRoomId, $subjectId);
        if (! $roomId) {
            return;
        }

        $conflict = TimetableSlot::query()
            ->where('academic_term_id', $termId)
            ->where('day', $day)
            ->where('period', $period)
            ->where('class_room_id', '!=', $classRoomId)
            ->whereExists(function ($query) use ($roomId): void {
                $query->selectRaw('1')
                    ->from('class_subject_settings')
                    ->whereColumn('class_subject_settings.class_room_id', 'timetable_slots.class_room_id')
                    ->whereColumn('class_subject_settings.subject_id', 'timetable_slots.subject_id')
                    ->where('class_subject_settings.room_id', $roomId)
                    ->where('class_subject_settings.is_active', true);
            })
            ->with(['classRoom', 'subject'])
            ->first();

        if (! $conflict) {
            return;
        }

        $conflictClass = $conflict->classRoom ? "{$conflict->classRoom->name} {$conflict->classRoom->section}" : 'another class';
        $conflictSubject = $conflict->subject?->name ?? 'another subject';

        $this->errors[] = [
            'type' => 'room_conflict',
            'day' => $day,
            'day_name' => $dayName,
            'period' => $period,
            'room_id' => $roomId,
            'message' => sprintf(
                'Assigned room/lab is already used by %s (%s) on %s Period %d.',
                $conflictClass,
                $conflictSubject,
                $dayName,
                $period,
            ),
        ];
    }

    /**
     * HARD: Check room/lab clashes across classes.
     */
    private function checkRoomConstraints(array $slots, int $termId, int $classRoomId): void
    {
        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                $subjectId = (int) $slot['subject_id'];
                $roomId = $this->getAssignedRoomId($classRoomId, $subjectId);

                if (! $roomId) {
                    continue;
                }

                $conflictExists = TimetableSlot::query()
                    ->where('academic_term_id', $termId)
                    ->where('day', $day)
                    ->where('period', $period)
                    ->where('class_room_id', '!=', $classRoomId)
                    ->whereExists(function ($query) use ($roomId): void {
                        $query->selectRaw('1')
                            ->from('class_subject_settings')
                            ->whereColumn('class_subject_settings.class_room_id', 'timetable_slots.class_room_id')
                            ->whereColumn('class_subject_settings.subject_id', 'timetable_slots.subject_id')
                            ->where('class_subject_settings.room_id', $roomId)
                            ->where('class_subject_settings.is_active', true);
                    })
                    ->exists();

                if ($conflictExists) {
                    $this->errors[] = [
                        'type' => 'room_conflict',
                        'day' => $day,
                        'day_name' => $dayName,
                        'period' => $period,
                        'room_id' => $roomId,
                        'message' => sprintf(
                            '%s has a room/lab clash on %s Period %d. The assigned special room is used by another class.',
                            $slot['subject_name'] ?? 'This subject',
                            $dayName,
                            $period,
                        ),
                    ];
                }
            }
        }
    }

    private function getAssignedRoomId(int $classRoomId, int $subjectId): ?int
    {
        $key = "{$classRoomId}:{$subjectId}";

        if (array_key_exists($key, $this->subjectRoomCache)) {
            return $this->subjectRoomCache[$key];
        }

        $roomId = ClassSubjectSetting::query()
            ->where('class_room_id', $classRoomId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->value('room_id');

        $this->subjectRoomCache[$key] = $roomId !== null ? (int) $roomId : null;

        return $this->subjectRoomCache[$key];
    }

    /**
     * @param  array<int, array<string, mixed>>  $issues
     * @return array<int, array<string, mixed>>
     */
    private function withSuggestions(array $issues): array
    {
        return array_map(function (array $issue): array {
            if (isset($issue['suggestion']) && is_string($issue['suggestion']) && $issue['suggestion'] !== '') {
                return $issue;
            }

            $type = (string) ($issue['type'] ?? '');
            $suggestion = match ($type) {
                'teacher_conflict' => 'Assign an available teacher for this period, or move one of the conflicting classes to another period.',
                'teacher_unavailable' => 'Pick a period where the teacher is available, or assign another teacher for this subject.',
                'teacher_workload' => 'Move one period to a less loaded day or split the load across additional qualified teachers.',
                'subject_daily_limit' => 'Spread this subject across different days to keep daily repetitions within limits.',
                'weekly_minimum' => 'Add more periods for this subject in currently empty or low-impact slots.',
                'weekly_maximum' => 'Reduce this subject by replacing extra periods with under-scheduled subjects.',
                'room_conflict' => 'Move one conflicting class to another period, or assign a different room/lab for one of the subjects.',
                'empty_slots' => 'Fill empty slots with pending required subjects while respecting teacher and room constraints.',
                'cognitive_load' => 'Insert a lighter subject between heavy subjects to improve learning flow.',
                default => null,
            };

            if ($suggestion !== null) {
                $issue['suggestion'] = $suggestion;
            }

            return $issue;
        }, $issues);
    }

    /**
     * Validate all soft constraints (warnings only)
     */
    private function validateSoftConstraints(array $slots, int $classRoomId): void
    {
        // 1. Check positional consistency
        $this->checkPositionalConsistency($slots);

        // 2. Check daily subject repetition
        $this->checkDailySubjectRepetition($slots, $classRoomId);

        // 3. Check core subject placement
        $this->checkCoreSubjectPlacement($slots);

        // 4. Check cognitive load (consecutive heavy subjects)
        $this->checkCognitiveLoad($slots);

        // 5. Check co-curricular period placement
        $this->checkCoCurricularPlacement($slots);
    }

    /**
     * HARD: Check timetable structure — all configured weekly periods must be filled.
     * The total is derived from the number of active days and the configured periods per day. Any empty slot is a hard violation.
     */
    private function checkTimetableStructure(array $slots): void
    {
        $activeDays = count($this->dayNames);
        $expectedTotal = $activeDays * $this->periodsPerDay;
        $emptySlots = [];

        foreach ($this->dayNames as $day => $dayName) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                if (! isset($slots[$day][$period]) || ! $slots[$day][$period]) {
                    $emptySlots[] = [
                        'day' => $day,
                        'period' => $period,
                        'day_name' => $dayName,
                    ];
                }
            }
        }

        if (count($emptySlots) > 0) {
            $this->errors[] = [
                'type' => 'empty_slots',
                'message' => sprintf(
                    '%d of %d weekly slots are empty. All %d periods must be filled (%d per day × %d days).',
                    count($emptySlots),
                    $expectedTotal,
                    $expectedTotal,
                    $this->periodsPerDay,
                    $activeDays
                ),
                'details' => $emptySlots,
            ];
        }
    }

    /**
     * HARD: Check subject daily limit (max 2 periods per subject per day)
     */
    private function checkSubjectDailyLimit(array $slots): void
    {
        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            $subjectCounts = [];

            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                $subjectId = $slot['subject_id'];
                $subjectName = $slot['subject_name'] ?? 'Unknown';

                if (! isset($subjectCounts[$subjectId])) {
                    $subjectCounts[$subjectId] = [
                        'name' => $subjectName,
                        'count' => 0,
                        'periods' => [],
                    ];
                }

                $subjectCounts[$subjectId]['count']++;
                $subjectCounts[$subjectId]['periods'][] = $period;
            }

            foreach ($subjectCounts as $subjectId => $data) {
                if ($data['count'] > $this->maxSubjectPeriodsPerDay) {
                    $this->errors[] = [
                        'type' => 'subject_daily_limit',
                        'day' => $day,
                        'day_name' => $dayName,
                        'subject_id' => $subjectId,
                        'message' => sprintf(
                            '%s appears %d times on %s (Max: %d periods per day)',
                            $data['name'],
                            $data['count'],
                            $dayName,
                            $this->maxSubjectPeriodsPerDay
                        ),
                        'periods' => $data['periods'],
                    ];
                }
            }
        }
    }

    /**
     * HARD: Check weekly min/max requirements
     */
    private function checkWeeklyRequirements(array $slots, int $classRoomId): void
    {
        // Get all subject settings for this class
        $subjectSettings = ClassSubjectSetting::where('class_room_id', $classRoomId)
            ->where('is_active', true)
            ->with('subject')
            ->get()
            ->keyBy('subject_id');

        // Count periods per subject across the week
        $subjectWeeklyCounts = [];

        foreach ($slots as $day => $periods) {
            foreach ($periods as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                $subjectId = $slot['subject_id'];
                if (! isset($subjectWeeklyCounts[$subjectId])) {
                    $subjectWeeklyCounts[$subjectId] = [
                        'count' => 0,
                        'name' => $slot['subject_name'] ?? 'Unknown',
                    ];
                }
                $subjectWeeklyCounts[$subjectId]['count']++;
            }
        }

        // Check each subject against its requirements
        foreach ($subjectSettings as $subjectId => $setting) {
            $actualCount = $subjectWeeklyCounts[$subjectId]['count'] ?? 0;
            $minRequired = $setting->min_periods_per_week ?? 0;
            $maxAllowed = $setting->max_periods_per_week ?? $setting->weekly_periods ?? 999;
            $subjectName = $setting->subject->name ?? 'Unknown';

            if ($actualCount < $minRequired) {
                $this->errors[] = [
                    'type' => 'weekly_minimum',
                    'subject_id' => $subjectId,
                    'message' => sprintf(
                        '%s has only %d periods (Min required: %d)',
                        $subjectName,
                        $actualCount,
                        $minRequired
                    ),
                ];
            }

            if ($actualCount > $maxAllowed) {
                $this->errors[] = [
                    'type' => 'weekly_maximum',
                    'subject_id' => $subjectId,
                    'message' => sprintf(
                        '%s has %d periods (Max allowed: %d)',
                        $subjectName,
                        $actualCount,
                        $maxAllowed
                    ),
                ];
            }
        }
    }

    /**
     * HARD: Check co-curricular subject rules (MOST COMPLEX)
     * - Only ONE co-curricular subject per day
     * - Max 2 periods per co-curricular per day
     * - Must be consecutive if 2 periods
     */
    private function checkCoCurricularRules(array $slots): void
    {
        // Load all co-curricular subjects
        $coCurricularSubjects = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('name', 'id')
            ->toArray();

        if (empty($coCurricularSubjects)) {
            return; // No co-curricular subjects to validate
        }

        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            $coCurricularOnDay = [];

            // Collect all co-curricular periods for this day
            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                if (isset($coCurricularSubjects[$slot['subject_id']])) {
                    $coCurricularOnDay[] = [
                        'period' => $period,
                        'subject_id' => $slot['subject_id'],
                        'subject_name' => $slot['subject_name'] ?? $coCurricularSubjects[$slot['subject_id']],
                    ];
                }
            }

            // Rule 1: Max 2 co-curricular periods total per day (any subject combination)
            if (count($coCurricularOnDay) > 2) {
                $this->errors[] = [
                    'type' => 'cocurricular_period_limit',
                    'day' => $day,
                    'day_name' => $dayName,
                    'message' => sprintf(
                        '%d co-curricular periods on %s (Max: 2 per day). Subjects: %s',
                        count($coCurricularOnDay),
                        $dayName,
                        implode(', ', array_unique(array_column($coCurricularOnDay, 'subject_name')))
                    ),
                    'periods' => array_column($coCurricularOnDay, 'period'),
                ];
            }

            // Rule 2: If the same subject appears twice, its two periods must be consecutive
            $bySubject = [];
            foreach ($coCurricularOnDay as $item) {
                $bySubject[$item['subject_id']][] = $item['period'];
            }

            foreach ($bySubject as $subjectId => $periodNumbers) {
                sort($periodNumbers);

                if (count($periodNumbers) === 2 && $periodNumbers[1] - $periodNumbers[0] !== 1) {
                    $subjectName = $coCurricularSubjects[$subjectId] ?? 'Unknown';
                    $this->errors[] = [
                        'type' => 'cocurricular_not_consecutive',
                        'day' => $day,
                        'day_name' => $dayName,
                        'subject_id' => $subjectId,
                        'message' => sprintf(
                            '%s appears twice on %s but periods %d and %d are not consecutive.',
                            $subjectName,
                            $dayName,
                            $periodNumbers[0],
                            $periodNumbers[1]
                        ),
                        'periods' => $periodNumbers,
                    ];
                }
            }
        }
    }

    /**
     * HARD: Check teacher constraints
     * - No double-booking (same teacher, same time, different classes)
     * - Max 6 periods per teacher per day
     */
    private function checkTeacherConstraints(array $slots, int $termId, int $classRoomId): void
    {
        // Check for teacher conflicts across classes
        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['teacher_id']) || ! $slot['teacher_id']) {
                    continue;
                }

                $teacher = Teacher::find($slot['teacher_id']);
                if ($teacher && ! $teacher->isAvailable($day, $period)) {
                    $this->errors[] = [
                        'type' => 'teacher_unavailable',
                        'day' => $day,
                        'day_name' => $dayName,
                        'period' => $period,
                        'teacher_id' => $slot['teacher_id'],
                        'message' => sprintf(
                            'Teacher %s is marked unavailable for %s Period %d',
                            $slot['teacher_name'] ?? $teacher->name,
                            $dayName,
                            $period
                        ),
                    ];
                }

                // Check for conflicts with other classes
                $conflicts = TimetableSlot::where('teacher_id', $slot['teacher_id'])
                    ->where('academic_term_id', $termId)
                    ->where('day', $day)
                    ->where('period', $period)
                    ->where('class_room_id', '!=', $classRoomId)
                    ->with(['classRoom', 'subject'])
                    ->get();

                foreach ($conflicts as $conflict) {
                    $conflictClass = $conflict->classRoom
                        ? "{$conflict->classRoom->name} {$conflict->classRoom->section}"
                        : 'another class';
                    $conflictSubject = $conflict->subject ? " ({$conflict->subject->name})" : '';

                    $this->errors[] = [
                        'type' => 'teacher_conflict',
                        'day' => $day,
                        'day_name' => $dayName,
                        'period' => $period,
                        'teacher_id' => $slot['teacher_id'],
                        'message' => sprintf(
                            'Teacher %s is already teaching %s%s at %s Period %d',
                            $slot['teacher_name'] ?? 'Unknown',
                            $conflictClass,
                            $conflictSubject,
                            $dayName,
                            $period
                        ),
                    ];
                }
            }
        }

        // Check max periods per teacher per day
        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            $teacherDailyCounts = [];

            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['teacher_id']) || ! $slot['teacher_id']) {
                    continue;
                }

                $teacherId = $slot['teacher_id'];
                if (! isset($teacherDailyCounts[$teacherId])) {
                    $teacherDailyCounts[$teacherId] = [
                        'name' => $slot['teacher_name'] ?? 'Unknown',
                        'count' => 0,
                        'periods' => [],
                    ];
                }

                $teacherDailyCounts[$teacherId]['count']++;
                $teacherDailyCounts[$teacherId]['periods'][] = $period;
            }

            foreach ($teacherDailyCounts as $teacherId => $data) {
                // Query the global total across ALL classes for this teacher + day
                $globalCount = TimetableSlot::where('teacher_id', $teacherId)
                    ->where('academic_term_id', $termId)
                    ->where('day', $day)
                    ->count();

                if ($globalCount > $this->maxTeacherPeriodsPerDay) {
                    $this->errors[] = [
                        'type' => 'teacher_workload',
                        'day' => $day,
                        'day_name' => $dayName,
                        'teacher_id' => $teacherId,
                        'message' => sprintf(
                            'Teacher %s has %d periods on %s across all classes (Max: %d per day)',
                            $data['name'],
                            $globalCount,
                            $dayName,
                            $this->maxTeacherPeriodsPerDay
                        ),
                        'periods' => $data['periods'],
                    ];
                }
            }
        }
    }

    /**
     * SOFT: Check positional consistency across days
     */
    private function checkPositionalConsistency(array $slots): void
    {
        // Track which subjects appear in which periods across days
        $subjectPositions = [];

        foreach ($slots as $day => $periods) {
            foreach ($periods as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                $subjectId = $slot['subject_id'];
                if (! isset($subjectPositions[$subjectId])) {
                    $subjectPositions[$subjectId] = [
                        'name' => $slot['subject_name'] ?? 'Unknown',
                        'positions' => [],
                    ];
                }

                $subjectPositions[$subjectId]['positions'][] = $period;
            }
        }

        // Check for inconsistent positioning
        foreach ($subjectPositions as $subjectId => $data) {
            $positions = $data['positions'];
            if (count($positions) < 2) {
                continue; // Need at least 2 occurrences to check consistency
            }

            // Calculate standard deviation to measure consistency
            $mean = array_sum($positions) / count($positions);
            $variance = array_sum(array_map(fn ($pos) => pow($pos - $mean, 2), $positions)) / count($positions);
            $stdDev = sqrt($variance);

            // If standard deviation > 2, warn about inconsistency
            if ($stdDev > 2) {
                $this->warnings[] = [
                    'type' => 'positional_inconsistency',
                    'subject_id' => $subjectId,
                    'message' => sprintf(
                        '%s appears at varying positions across days (periods: %s). Consider more consistent placement.',
                        $data['name'],
                        implode(', ', $positions)
                    ),
                    'severity' => 'low',
                ];
            }
        }
    }

    /**
     * SOFT: Check daily subject repetition (prefer one occurrence per day)
     */
    private function checkDailySubjectRepetition(array $slots, int $classRoomId): void
    {
        // Get min requirements to know if repetition is necessary
        $subjectSettings = ClassSubjectSetting::where('class_room_id', $classRoomId)
            ->where('is_active', true)
            ->with('subject')
            ->get()
            ->keyBy('subject_id');

        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            $subjectCounts = [];

            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                $subjectId = $slot['subject_id'];
                $subjectName = $slot['subject_name'] ?? 'Unknown';

                if (! isset($subjectCounts[$subjectId])) {
                    $subjectCounts[$subjectId] = [
                        'name' => $subjectName,
                        'count' => 0,
                    ];
                }

                $subjectCounts[$subjectId]['count']++;
            }

            foreach ($subjectCounts as $subjectId => $data) {
                if ($data['count'] > 1) {
                    // Check if this is necessary due to high weekly requirements
                    $setting = $subjectSettings[$subjectId] ?? null;
                    $minRequired = $setting ? $setting->min_periods_per_week : 0;

                    // If subject needs many periods per week, repetition might be necessary
                    if ($minRequired > 8) {
                        continue; // Skip warning for high-demand subjects
                    }

                    $this->warnings[] = [
                        'type' => 'daily_repetition',
                        'day' => $day,
                        'day_name' => $dayName,
                        'subject_id' => $subjectId,
                        'message' => sprintf(
                            '%s appears %d times on %s. Consider spreading across different days.',
                            $data['name'],
                            $data['count'],
                            $dayName
                        ),
                        'severity' => 'low',
                    ];
                }
            }
        }
    }

    /**
     * SOFT: Check core subject placement consistency
     */
    private function checkCoreSubjectPlacement(array $slots): void
    {
        $coreSubjectPositions = [];

        // Collect positions for core subjects
        foreach ($slots as $day => $periods) {
            foreach ($periods as $period => $slot) {
                if (! $slot || ! isset($slot['subject_name'])) {
                    continue;
                }

                $subjectName = $slot['subject_name'];

                // Check if it's a core subject (case-insensitive partial match)
                foreach ($this->coreSubjects as $coreSubject) {
                    if (stripos($subjectName, $coreSubject) !== false) {
                        if (! isset($coreSubjectPositions[$coreSubject])) {
                            $coreSubjectPositions[$coreSubject] = [];
                        }
                        $coreSubjectPositions[$coreSubject][] = [
                            'day' => $day,
                            'period' => $period,
                        ];
                        break;
                    }
                }
            }
        }

        // Check if core subjects are in consistent periods
        foreach ($coreSubjectPositions as $coreSubject => $positions) {
            if (count($positions) < 2) {
                continue;
            }

            $periods = array_column($positions, 'period');
            $uniquePeriods = array_unique($periods);

            // If core subject appears in more than 2 different periods, warn
            if (count($uniquePeriods) > 2) {
                $this->warnings[] = [
                    'type' => 'core_placement_inconsistent',
                    'message' => sprintf(
                        'Core subject %s appears in %d different periods (%s). Consider consistent placement.',
                        $coreSubject,
                        count($uniquePeriods),
                        implode(', ', $uniquePeriods)
                    ),
                    'severity' => 'medium',
                ];
            }
        }
    }

    /**
     * SOFT: Check cognitive load (avoid consecutive heavy subjects)
     */
    private function checkCognitiveLoad(array $slots): void
    {
        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            // Check consecutive periods
            for ($period = 1; $period < 8; $period++) {
                $currentSlot = $slots[$day][$period] ?? null;
                $nextSlot = $slots[$day][$period + 1] ?? null;

                if (! $currentSlot || ! $nextSlot) {
                    continue;
                }

                $currentHeavy = $this->isHeavySubject($currentSlot['subject_name'] ?? '');
                $nextHeavy = $this->isHeavySubject($nextSlot['subject_name'] ?? '');

                if ($currentHeavy && $nextHeavy) {
                    $this->warnings[] = [
                        'type' => 'cognitive_load',
                        'day' => $day,
                        'day_name' => $dayName,
                        'period' => $period,
                        'message' => sprintf(
                            'Consecutive demanding subjects on %s: %s (P%d) → %s (P%d). Consider spacing them out.',
                            $dayName,
                            $currentSlot['subject_name'],
                            $period,
                            $nextSlot['subject_name'],
                            $period + 1
                        ),
                        'severity' => 'medium',
                    ];
                }
            }
        }
    }

    /**
     * SOFT: Check co-curricular period placement (should be middle or last)
     */
    private function checkCoCurricularPlacement(array $slots): void
    {
        $coCurricularSubjects = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        if (empty($coCurricularSubjects)) {
            return;
        }

        foreach ($this->dayNames as $day => $dayName) {
            if (! isset($slots[$day])) {
                continue;
            }

            foreach ($slots[$day] as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                if (in_array($slot['subject_id'], $coCurricularSubjects)) {
                    // Check if in early periods (1-3)
                    if (! in_array($period, $this->preferredCoCurricularPeriods)) {
                        $this->warnings[] = [
                            'type' => 'cocurricular_early_placement',
                            'day' => $day,
                            'day_name' => $dayName,
                            'period' => $period,
                            'message' => sprintf(
                                'Co-curricular %s is in period %d on %s. Consider placing in middle or later periods (4-8).',
                                $slot['subject_name'] ?? 'Unknown',
                                $period,
                                $dayName
                            ),
                            'severity' => 'low',
                        ];
                    }
                }
            }
        }
    }

    /**
     * Helper: Check if a subject is mentally heavy
     */
    private function isHeavySubject(string $subjectName): bool
    {
        foreach ($this->heavySubjects as $heavy) {
            if (stripos($subjectName, $heavy) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Check if a subject is a physical activity (must not appear in period 5)
     */
    private function isPhysicalSubject(string $subjectName): bool
    {
        foreach ($this->physicalSubjects as $physical) {
            if (stripos($subjectName, $physical) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * HARD: Subject allocations must be balanced across the week.
     * If a subject appears on fewer available days than possible and is concentrated on
     * one day (2 periods) while other days could absorb one period each, flag it.
     */
    private function checkSubjectWeeklyBalance(array $slots): void
    {
        $subjectDayCounts = [];

        foreach ($slots as $day => $periods) {
            foreach ($periods as $period => $slot) {
                if (! $slot || ! isset($slot['subject_id'])) {
                    continue;
                }

                $subjectId = $slot['subject_id'];
                if (! isset($subjectDayCounts[$subjectId])) {
                    $subjectDayCounts[$subjectId] = [
                        'name' => $slot['subject_name'] ?? 'Unknown',
                        'by_day' => [],
                        'total' => 0,
                    ];
                }

                $subjectDayCounts[$subjectId]['by_day'][$day] =
                    ($subjectDayCounts[$subjectId]['by_day'][$day] ?? 0) + 1;
                $subjectDayCounts[$subjectId]['total']++;
            }
        }

        $activeDayCount = count($this->dayNames);

        foreach ($subjectDayCounts as $subjectId => $data) {
            $dayCounts = array_values($data['by_day']);

            if (count($dayCounts) < 2 || $data['total'] < 2) {
                continue;
            }

            $maxOnOneDay = max($dayCounts);
            $daysUsed = count($dayCounts);
            $availableDays = $activeDayCount - $daysUsed;

            // A subject is considered overloaded when it appears 2× on a single day
            // yet free days remain where it could be spread instead
            if ($maxOnOneDay >= 2 && $availableDays > 0) {
                $this->errors[] = [
                    'type' => 'subject_overloaded_day',
                    'subject_id' => $subjectId,
                    'message' => sprintf(
                        '%s is overloaded: %d periods on a single day with %d day(s) still available. Spread allocations evenly across the week.',
                        $data['name'],
                        $maxOnOneDay,
                        $availableDays
                    ),
                ];
            }
        }
    }

    /**
     * HARD: Combined subjects may only span sections of the same grade — not cross-grade.
     * E.g. Sport for Class 1 Section A & B is allowed; Sport for Class 1 & Class 2 is not.
     */
    private function checkCombinedSubjectRules(int $classRoomId, int $termId): void
    {
        $combinedPeriods = CombinedPeriod::where('academic_term_id', $termId)
            ->with('subject')
            ->get();

        if ($combinedPeriods->isEmpty()) {
            return;
        }

        foreach ($combinedPeriods as $combined) {
            $classRoomIds = $combined->class_room_ids ?? [];

            if (count($classRoomIds) < 2) {
                continue;
            }

            // All rooms must belong to the same grade, which in this system is represented by ClassRoom::name
            $grades = ClassRoom::whereIn('id', $classRoomIds)->pluck('name')->unique();

            if ($grades->count() > 1) {
                $this->errors[] = [
                    'type' => 'combined_cross_grade',
                    'message' => sprintf(
                        'Combined subject "%s" spans multiple grades (%s). Combined subjects must be within the same grade only (e.g. Class 1 Section A and B — not Class 1 and Class 2).',
                        $combined->subject?->name ?? 'Unknown',
                        $grades->implode(', ')
                    ),
                ];
            }
        }
    }

    /**
     * HARD: Physical activity subjects (sports, taekwondo, dance) must not appear in period 5.
     */
    private function checkPhysicalSubjectFifthPeriod(array $slots): void
    {
        foreach ($this->dayNames as $day => $dayName) {
            $slot = $slots[$day][5] ?? null;

            if (! $slot || ! isset($slot['subject_name'])) {
                continue;
            }

            if ($this->isPhysicalSubject($slot['subject_name'])) {
                $this->errors[] = [
                    'type' => 'physical_fifth_period',
                    'day' => $day,
                    'day_name' => $dayName,
                    'period' => 5,
                    'message' => sprintf(
                        '%s (physical activity) cannot be scheduled in period 5 on %s.',
                        $slot['subject_name'],
                        $dayName
                    ),
                ];
            }
        }
    }
}
