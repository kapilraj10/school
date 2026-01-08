<?php

namespace App\Services;

use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;

class TimetableValidationService
{
    private const MAX_TEACHER_PERIODS_PER_DAY = 6;

    private const MAX_SUBJECT_PERIODS_PER_DAY = 2;

    private const CORE_SUBJECTS = ['English', 'Maths', 'Science', 'Nepali', 'Math'];

    private const HEAVY_SUBJECTS = ['Maths', 'Math', 'Science', 'English', 'Nepali', 'Social'];

    private const PREFERRED_COCURRICULAR_PERIODS = [4, 5, 6, 7, 8]; // Middle and last periods

    private array $errors = [];

    private array $warnings = [];

    private array $dayNames = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Validate a single slot assignment before saving
     *
     * @param  int  $classRoomId  The class room ID
     * @param  int  $termId  The academic term ID
     * @param  int  $subjectId  The subject ID to assign
     * @param  int|null  $teacherId  The teacher ID to assign
     * @param  int  $day  Day of week (1-6)
     * @param  int  $period  Period number (1-8)
     * @return array ['errors' => [], 'warnings' => []]
     */
    public function validateSlotAssignment(
        int $classRoomId,
        int $termId,
        int $subjectId,
        ?int $teacherId,
        int $day,
        int $period
    ): array {
        $this->errors = [];
        $this->warnings = [];

        // Load existing slots for this class
        $existingSlots = $this->loadExistingSlotsAsArray($classRoomId, $termId);

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
            'errors' => $this->errors,
            'warnings' => $this->warnings,
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

        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'has_errors' => count($this->errors) > 0,
            'has_warnings' => count($this->warnings) > 0,
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

        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'has_errors' => count($this->errors) > 0,
            'has_warnings' => count($this->warnings) > 0,
        ];
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

        if ($subjectCountToday >= self::MAX_SUBJECT_PERIODS_PER_DAY) {
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
                    self::MAX_SUBJECT_PERIODS_PER_DAY
                ),
            ];
        }

        // 2. Check co-curricular rules
        if ($subject->type === 'co_curricular') {
            $this->validateCoCurricularSlot($existingSlots, $subject, $day, $period, $dayName);
        }

        // 3. Check teacher constraints
        if ($teacher) {
            $this->validateTeacherSlot($teacher, $day, $period, $dayName, $termId, $classRoomId);
        }

        // 4. Check weekly requirements (warning if needed)
        $this->checkWeeklySingleSlot($existingSlots, $subject, $classRoomId);

        // 5. Soft validation - check if subject already appears today
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
        // Check if there's already a different co-curricular subject today
        $coCurricularSubjects = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        $existingCoCurricular = [];
        $thisSubjectPeriods = [];

        foreach ($existingSlots[$day] ?? [] as $p => $slot) {
            if (in_array($slot['subject_id'], $coCurricularSubjects)) {
                if ($slot['subject_id'] !== $subject->id) {
                    $existingCoCurricular[] = $slot['subject_name'];
                } else {
                    $thisSubjectPeriods[] = $p;
                }
            }
        }

        // Rule 1: Only one co-curricular subject per day
        if (! empty($existingCoCurricular)) {
            $this->errors[] = [
                'type' => 'multiple_cocurricular',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Cannot assign %s - another co-curricular subject (%s) is already scheduled on %s. Only 1 co-curricular per day allowed.',
                    $subject->name,
                    implode(', ', $existingCoCurricular),
                    $dayName
                ),
            ];
        }

        // Rule 2: Max 2 periods per co-curricular per day
        if (count($thisSubjectPeriods) >= 2) {
            $this->errors[] = [
                'type' => 'cocurricular_period_limit',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    '%s already has 2 periods on %s (maximum allowed). Cannot add another.',
                    $subject->name,
                    $dayName
                ),
            ];
        }

        // Rule 3: If adding a second period, must be consecutive
        if (count($thisSubjectPeriods) === 1) {
            $existingPeriod = $thisSubjectPeriods[0];
            if (abs($period - $existingPeriod) !== 1) {
                $this->errors[] = [
                    'type' => 'cocurricular_not_consecutive',
                    'day' => $day,
                    'day_name' => $dayName,
                    'period' => $period,
                    'message' => sprintf(
                        '%s period %d must be consecutive to existing period %d on %s.',
                        $subject->name,
                        $period,
                        $existingPeriod,
                        $dayName
                    ),
                ];
            }
        }

        // Soft warning: co-curricular should be in middle/later periods
        if (! in_array($period, self::PREFERRED_COCURRICULAR_PERIODS)) {
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
        // Check for double-booking (teacher in different class at same time)
        $conflict = TimetableSlot::where('teacher_id', $teacher->id)
            ->where('academic_term_id', $termId)
            ->where('day', $day)
            ->where('period', $period)
            ->where('class_room_id', '!=', $classRoomId)
            ->with('classRoom')
            ->first();

        if ($conflict) {
            $this->errors[] = [
                'type' => 'teacher_conflict',
                'day' => $day,
                'day_name' => $dayName,
                'period' => $period,
                'message' => sprintf(
                    'Teacher %s is already assigned to %s at %s Period %d',
                    $teacher->name,
                    $conflict->classRoom->name ?? 'another class',
                    $dayName,
                    $period
                ),
            ];
        }

        // Check daily workload (max 6 periods per day)
        $periodsToday = TimetableSlot::where('teacher_id', $teacher->id)
            ->where('academic_term_id', $termId)
            ->where('day', $day)
            ->where('class_room_id', $classRoomId)
            ->count();

        if ($periodsToday >= self::MAX_TEACHER_PERIODS_PER_DAY) {
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
                    self::MAX_TEACHER_PERIODS_PER_DAY
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
     * HARD: Check timetable structure - warn about empty slots
     */
    private function checkTimetableStructure(array $slots): void
    {
        $totalSlots = 0;
        $filledSlots = 0;
        $emptySlots = [];

        foreach ($this->dayNames as $day => $dayName) {
            for ($period = 1; $period <= 8; $period++) {
                $totalSlots++;
                if (isset($slots[$day][$period]) && $slots[$day][$period]) {
                    $filledSlots++;
                } else {
                    $emptySlots[] = [
                        'day' => $day,
                        'period' => $period,
                        'day_name' => $dayName,
                    ];
                }
            }
        }

        if (count($emptySlots) > 0) {
            $this->warnings[] = [
                'type' => 'empty_slots',
                'message' => sprintf('%d slots are empty. Complete timetable allocation recommended.', count($emptySlots)),
                'details' => $emptySlots,
                'severity' => 'info',
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
                if ($data['count'] > self::MAX_SUBJECT_PERIODS_PER_DAY) {
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
                            self::MAX_SUBJECT_PERIODS_PER_DAY
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
            $uniqueCoCurricularSubjects = [];

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
                    $uniqueCoCurricularSubjects[$slot['subject_id']] = $slot['subject_name'] ?? $coCurricularSubjects[$slot['subject_id']];
                }
            }

            // Rule 1: Only ONE co-curricular subject per day
            if (count($uniqueCoCurricularSubjects) > 1) {
                $this->errors[] = [
                    'type' => 'multiple_cocurricular',
                    'day' => $day,
                    'day_name' => $dayName,
                    'message' => sprintf(
                        'Multiple co-curricular subjects on %s: %s (Only 1 allowed per day)',
                        $dayName,
                        implode(', ', $uniqueCoCurricularSubjects)
                    ),
                    'periods' => array_column($coCurricularOnDay, 'period'),
                ];
            }

            // Rule 2 & 3: Max 2 periods and must be consecutive
            foreach ($uniqueCoCurricularSubjects as $subjectId => $subjectName) {
                $periodsForThisSubject = array_filter($coCurricularOnDay, fn ($item) => $item['subject_id'] === $subjectId);
                $periodNumbers = array_column($periodsForThisSubject, 'period');
                sort($periodNumbers);

                if (count($periodNumbers) > 2) {
                    $this->errors[] = [
                        'type' => 'cocurricular_period_limit',
                        'day' => $day,
                        'day_name' => $dayName,
                        'subject_id' => $subjectId,
                        'message' => sprintf(
                            '%s appears %d times on %s (Max: 2 periods)',
                            $subjectName,
                            count($periodNumbers),
                            $dayName
                        ),
                        'periods' => $periodNumbers,
                    ];
                }

                // Check if consecutive (only relevant if 2 periods)
                if (count($periodNumbers) === 2) {
                    if ($periodNumbers[1] - $periodNumbers[0] !== 1) {
                        $this->errors[] = [
                            'type' => 'cocurricular_not_consecutive',
                            'day' => $day,
                            'day_name' => $dayName,
                            'subject_id' => $subjectId,
                            'message' => sprintf(
                                '%s periods on %s are not consecutive (Periods %d and %d)',
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

                // Check for conflicts with other classes
                $conflicts = TimetableSlot::where('teacher_id', $slot['teacher_id'])
                    ->where('academic_term_id', $termId)
                    ->where('day', $day)
                    ->where('period', $period)
                    ->where('class_room_id', '!=', $classRoomId)
                    ->with('classRoom')
                    ->get();

                foreach ($conflicts as $conflict) {
                    $this->errors[] = [
                        'type' => 'teacher_conflict',
                        'day' => $day,
                        'day_name' => $dayName,
                        'period' => $period,
                        'teacher_id' => $slot['teacher_id'],
                        'message' => sprintf(
                            'Teacher %s is already assigned to %s at this time',
                            $slot['teacher_name'] ?? 'Unknown',
                            $conflict->classRoom->name ?? 'another class'
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
                if ($data['count'] > self::MAX_TEACHER_PERIODS_PER_DAY) {
                    $this->errors[] = [
                        'type' => 'teacher_workload',
                        'day' => $day,
                        'day_name' => $dayName,
                        'teacher_id' => $teacherId,
                        'message' => sprintf(
                            'Teacher %s has %d periods on %s (Max: %d)',
                            $data['name'],
                            $data['count'],
                            $dayName,
                            self::MAX_TEACHER_PERIODS_PER_DAY
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
                foreach (self::CORE_SUBJECTS as $coreSubject) {
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
                    if (! in_array($period, self::PREFERRED_COCURRICULAR_PERIODS)) {
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
        foreach (self::HEAVY_SUBJECTS as $heavy) {
            if (stripos($subjectName, $heavy) !== false) {
                return true;
            }
        }

        return false;
    }
}
