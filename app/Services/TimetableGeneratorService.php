<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRange;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableGeneratorService
{
    private array $days;

    private int $periodsPerDay;

    private int $maxSameSubjectPerDay;

    private array $dayShortMap = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
    ];

    private array $dayIndexToName = [];

    private array $errors = [];

    private array $warnings = [];

    private array $teacherSchedule = [];

    // === New properties from Algorithm.php ===

    // Track global teacher occupancy across all classes
    private array $globalTeacherOccupancy = [];

    // Track teacher workload per day
    private array $teacherDailyWorkload = [];

    // Max periods per teacher per day (hard constraint)
    private int $maxTeacherPeriodsPerDay = 6;

    // Mentally heavy subjects (avoid consecutive)
    private array $heavySubjects = ['Maths', 'Science', 'English', 'Nepali', 'Social'];

    // Core subjects for positional consistency
    private array $coreSubjects = ['English', 'Maths', 'Science', 'Nepali'];

    // Preferred periods for ECA (middle and last)
    private array $preferredEcaPeriods = [4, 5, 6, 7, 8];

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Load settings from database or use defaults
     */
    private function loadSettings(): void
    {
        // Load school days from settings
        $schoolDays = TimetableSetting::get('school_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        $this->days = is_array($schoolDays) ? $schoolDays : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // Build day index to name mapping
        $this->dayIndexToName = [];
        foreach ($this->days as $index => $day) {
            $this->dayIndexToName[$index] = $day;
        }

        // Load other settings
        $this->periodsPerDay = (int) TimetableSetting::get('periods_per_day', 8);
        $this->maxSameSubjectPerDay = (int) TimetableSetting::get('max_same_subject_per_day', 2);
    }

    /**
     * Get class range for a class number - uses ClassRange model
     */
    private function getClassRangeForClassNumber(int $classNumber): string
    {
        return ClassRange::getRangeNameForClass($classNumber);
    }

    /**
     * Extract class number from class name (e.g., "Class 5" => 5)
     */
    private function extractClassNumber(string $className): int
    {
        if (preg_match('/(\d+)/', $className, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * Get subjects for a specific class using ClassSubjectSettings or fallback to Subject table
     */
    private function getSubjectsForClass(ClassRoom $class): Collection
    {
        $classSettings = ClassSubjectSetting::with('subject')
            ->where('class_room_id', $class->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        if ($classSettings->isNotEmpty()) {
            return $classSettings;
        }

        return Subject::where('class_room_id', $class->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($subject) {
                return (object) [
                    'subject' => $subject,
                    'subject_id' => $subject->id,
                    'min_periods_per_week' => $subject->min_periods_per_week ?? 1,
                    'max_periods_per_week' => $subject->max_periods_per_week ?? 6,
                    'weekly_periods' => $subject->weekly_periods ?? 4,
                    'single_combined' => $subject->single_combined ?? 'single',
                    'priority' => 5,
                ];
            });
    }

    private function getTeacherForSubject(Subject $subject, int $classRoomId): ?Teacher
    {
        $teachers = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->get();

        foreach ($teachers as $teacher) {
            if ($teacher->canTeachClass($classRoomId)) {
                return $teacher;
            }
        }

        return null;
    }

    /**
     * Check if teacher is available on a specific day and period - matching Algorithm.php
     */
    private function isTeacherAvailable(Teacher $teacher, string $day, int $period): bool
    {
        $shortDay = $this->dayShortMap[$day] ?? '';

        $availableDays = $teacher->available_days ?? [];
        $availablePeriods = $teacher->available_periods ?? [];

        // If no restrictions set, teacher is available
        if (empty($availableDays) && empty($availablePeriods)) {
            return true;
        }

        // Check day availability (stored as strings like 'Sun', 'Mon', etc.)
        $dayAvailable = empty($availableDays) || in_array($shortDay, $availableDays, true);

        // Check period availability (may be stored as int or string)
        $periodAvailable = empty($availablePeriods);
        if (! $periodAvailable) {
            // Check both int and string versions for compatibility
            $periodAvailable = in_array($period, $availablePeriods, true)
                || in_array((string) $period, $availablePeriods, true);
        }

        return $dayAvailable && $periodAvailable;
    }

    /**
     * Check if teacher is already assigned at this time slot
     */
    private function isTeacherBusy(int $teacherId, int $dayIndex, int $period): bool
    {
        return isset($this->teacherSchedule[$teacherId][$dayIndex][$period]);
    }

    /**
     * Mark teacher as busy at this time slot
     */
    private function markTeacherBusy(int $teacherId, int $dayIndex, int $period): void
    {
        if (! isset($this->teacherSchedule[$teacherId])) {
            $this->teacherSchedule[$teacherId] = [];
        }
        if (! isset($this->teacherSchedule[$teacherId][$dayIndex])) {
            $this->teacherSchedule[$teacherId][$dayIndex] = [];
        }
        $this->teacherSchedule[$teacherId][$dayIndex][$period] = true;
    }

    // === Hard Constraint Methods from Algorithm.php ===

    /**
     * Check if teacher is already assigned to another class at this slot (global)
     */
    private function isTeacherOccupiedGlobal(int $teacherId, int $dayIndex, int $period): bool
    {
        $key = "{$teacherId}_{$dayIndex}_{$period}";

        return isset($this->globalTeacherOccupancy[$key]);
    }

    /**
     * Mark teacher as occupied at a slot (global tracking)
     */
    private function markTeacherOccupiedGlobal(int $teacherId, int $dayIndex, int $period, int $classId): void
    {
        $key = "{$teacherId}_{$dayIndex}_{$period}";
        $this->globalTeacherOccupancy[$key] = $classId;

        $workloadKey = "{$teacherId}_{$dayIndex}";
        if (! isset($this->teacherDailyWorkload[$workloadKey])) {
            $this->teacherDailyWorkload[$workloadKey] = 0;
        }
        $this->teacherDailyWorkload[$workloadKey]++;
    }

    /**
     * Check if teacher has exceeded daily workload limit
     */
    private function hasExceededDailyWorkload(int $teacherId, int $dayIndex): bool
    {
        $workloadKey = "{$teacherId}_{$dayIndex}";
        $currentWorkload = $this->teacherDailyWorkload[$workloadKey] ?? 0;

        return $currentWorkload >= $this->maxTeacherPeriodsPerDay;
    }

    /**
     * Count subject occurrences on a specific day
     */
    private function countSubjectOnDay(array $timetable, int $dayIndex, int $subjectId): int
    {
        $count = 0;
        if (! isset($timetable[$dayIndex])) {
            return $count;
        }
        foreach ($timetable[$dayIndex] as $slot) {
            if ($slot && $slot['subject_id'] === $subjectId) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if any ECA subject is on a day
     */
    private function hasAnyEcaOnDay(array $timetable, int $dayIndex, array $ecaSubjectIds): bool
    {
        if (! isset($timetable[$dayIndex])) {
            return false;
        }
        foreach ($timetable[$dayIndex] as $slot) {
            if ($slot && in_array($slot['subject_id'], $ecaSubjectIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the ECA subject ID on a day (if any)
     */
    private function getEcaSubjectOnDay(array $timetable, int $dayIndex, array $ecaSubjectIds): ?int
    {
        if (! isset($timetable[$dayIndex])) {
            return null;
        }
        foreach ($timetable[$dayIndex] as $slot) {
            if ($slot && in_array($slot['subject_id'], $ecaSubjectIds)) {
                return $slot['subject_id'];
            }
        }

        return null;
    }

    /**
     * Count specific ECA subject periods on a day
     */
    private function countSpecificEcaOnDay(array $timetable, int $dayIndex, int $subjectId): int
    {
        $count = 0;
        if (! isset($timetable[$dayIndex])) {
            return $count;
        }
        foreach ($timetable[$dayIndex] as $slot) {
            if ($slot && $slot['subject_id'] === $subjectId) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if ECA placement would be consecutive with existing ECA
     */
    private function isConsecutiveEcaPlacement(array $timetable, int $dayIndex, int $period, int $subjectId, int $periodsPerDay): bool
    {
        if ($period > 1) {
            $prevSlot = $timetable[$dayIndex][$period - 1] ?? null;
            if ($prevSlot && $prevSlot['subject_id'] === $subjectId) {
                return true;
            }
        }
        if ($period < $periodsPerDay) {
            $nextSlot = $timetable[$dayIndex][$period + 1] ?? null;
            if ($nextSlot && $nextSlot['subject_id'] === $subjectId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a slot can be assigned (all hard constraints)
     */
    private function canAssignSlot(
        array $timetable,
        int $dayIndex,
        int $period,
        Subject $subject,
        ?Teacher $teacher,
        int $classId,
        array $ecaSubjectIds,
        int $periodsPerDay
    ): bool {
        $dayName = $this->dayIndexToName[$dayIndex] ?? 'Monday';

        // 1. Teacher availability
        if ($teacher && ! $this->isTeacherAvailable($teacher, $dayName, $period)) {
            return false;
        }

        // 2. Teacher not double-booked (local)
        if ($teacher && $this->isTeacherBusy($teacher->id, $dayIndex, $period)) {
            return false;
        }

        // 3. Teacher not double-booked (global)
        if ($teacher && $this->isTeacherOccupiedGlobal($teacher->id, $dayIndex, $period)) {
            return false;
        }

        // 4. Teacher daily workload limit
        if ($teacher && $this->hasExceededDailyWorkload($teacher->id, $dayIndex)) {
            return false;
        }

        // 5. Subject daily limit (max 2 per day)
        if ($this->countSubjectOnDay($timetable, $dayIndex, $subject->id) >= $this->maxSameSubjectPerDay) {
            return false;
        }

        // 6. ECA constraints
        if (in_array($subject->id, $ecaSubjectIds)) {
            $existingEca = $this->getEcaSubjectOnDay($timetable, $dayIndex, $ecaSubjectIds);
            if ($existingEca !== null) {
                if ($existingEca !== $subject->id) {
                    return false; // Different ECA not allowed
                }
                $ecaCount = $this->countSpecificEcaOnDay($timetable, $dayIndex, $subject->id);
                if ($ecaCount >= 2) {
                    return false;
                }
                if (! $this->isConsecutiveEcaPlacement($timetable, $dayIndex, $period, $subject->id, $periodsPerDay)) {
                    return false;
                }
            }
        }

        return true;
    }

    // === Soft Constraint Methods ===

    /**
     * Create a daily template for positional consistency
     * Core subjects (English, Math, Science, Nepali) get early positions
     */
    private function createDailyTemplate(array $compulsory, int $periodsPerDay): array
    {
        $template = [];
        $position = 1;

        // Core subjects first in order
        foreach ($this->coreSubjects as $coreName) {
            foreach ($compulsory as $item) {
                if (str_contains(strtolower($item['subject']->name), strtolower($coreName))) {
                    $template[$item['subject']->id] = $position++;
                    break;
                }
            }
        }

        // Other compulsory subjects in remaining positions
        foreach ($compulsory as $item) {
            if (! isset($template[$item['subject']->id])) {
                $template[$item['subject']->id] = $position++;
            }
        }

        return $template;
    }

    /**
     * Check if a subject is considered "heavy" (mentally demanding)
     */
    private function isHeavySubject(Subject $subject): bool
    {
        foreach ($this->heavySubjects as $heavyName) {
            if (str_contains(strtolower($subject->name), strtolower($heavyName))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a subject ID is heavy
     */
    private function isHeavySubjectById(int $subjectId): bool
    {
        $subject = Subject::find($subjectId);
        if (! $subject) {
            return false;
        }

        return $this->isHeavySubject($subject);
    }

    /**
     * Check if placing a heavy subject would create consecutive heavy subjects
     * Returns true if this placement should be penalized/avoided
     */
    private function hasConsecutiveHeavySubject(array $timetable, int $dayIndex, int $period, Subject $subject): bool
    {
        if (! $this->isHeavySubject($subject)) {
            return false;
        }

        // Check previous period
        if ($period > 1) {
            $prevSlot = $timetable[$dayIndex][$period - 1] ?? null;
            if ($prevSlot && $this->isHeavySubjectById($prevSlot['subject_id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate that all periods are filled
     */
    private function validateFullAllocation(array $timetable, int $periodsPerDay, int $daysCount): void
    {
        $emptyCount = 0;
        for ($day = 0; $day < $daysCount; $day++) {
            for ($period = 1; $period <= $periodsPerDay; $period++) {
                if (! isset($timetable[$day][$period]) || $timetable[$day][$period] === null) {
                    $emptyCount++;
                }
            }
        }

        if ($emptyCount > 0) {
            $this->warnings[] = "{$emptyCount} periods remain unfilled";
        }
    }

    /**
     * Main generation method - matching Algorithm.php structure
     */
    public function generate(array $classIds, int $termId, array $options = []): array
    {
        // Reload settings in case they changed
        $this->loadSettings();

        $this->errors = [];
        $this->warnings = [];
        $this->teacherSchedule = [];
        $this->globalTeacherOccupancy = [];
        $this->teacherDailyWorkload = [];

        try {
            DB::beginTransaction();

            // Clear existing timetable for these classes if option is set (except locked slots)
            if ($options['clear_existing'] ?? true) {
                TimetableSlot::whereIn('class_room_id', $classIds)
                    ->where('academic_term_id', $termId)
                    ->where('is_locked', false)
                    ->delete();
            }

            $classes = ClassRoom::whereIn('id', $classIds)->get();
            $term = AcademicTerm::findOrFail($termId);

            // Step 1: Place combined periods first
            $this->placeCombinedPeriods($classes, $term);

            // Step 2: Generate for each class using the Algorithm.php logic
            foreach ($classes as $class) {
                $this->generateTimetableForClass($class, $term);
            }

            DB::commit();

            return [
                'success' => empty($this->errors),
                'errors' => $this->errors,
                'warnings' => $this->warnings,
                'statistics' => $this->generateStatistics($classIds, $termId),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Generation failed: {$e->getMessage()}";

            return [
                'success' => false,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        }
    }

    /**
     * Place combined periods (martial arts, etc.)
     */
    private function placeCombinedPeriods(Collection $classes, AcademicTerm $term): void
    {
        $combinedPeriods = CombinedPeriod::where('academic_term_id', $term->id)->get();

        foreach ($combinedPeriods as $combined) {
            $classIds = $combined->class_room_ids;

            foreach ($classIds as $classId) {
                if (! in_array($classId, $classes->pluck('id')->toArray())) {
                    continue;
                }

                TimetableSlot::create([
                    'class_room_id' => $classId,
                    'subject_id' => $combined->subject_id,
                    'teacher_id' => $combined->teacher_id,
                    'day' => $combined->day,
                    'period' => $combined->period,
                    'is_combined' => true,
                    'combined_period_id' => $combined->id,
                    'academic_term_id' => $term->id,
                ]);

                // Mark teacher as busy
                if ($combined->teacher_id) {
                    $this->markTeacherBusy($combined->teacher_id, $combined->day, $combined->period);
                }
            }
        }
    }

    /**
     * Generate timetable for a single class - uses Algorithm.php constraint logic
     */
    private function generateTimetableForClass(ClassRoom $class, AcademicTerm $term): void
    {
        $classNumber = $this->extractClassNumber($class->name);
        $classRange = $this->getClassRangeForClassNumber($classNumber);

        // Get class range settings for periods per day
        $classRangeModel = ClassRange::getForClassNumber($classNumber);
        $periodsPerDay = $classRangeModel?->periods_per_day ?? $this->periodsPerDay;
        $daysCount = count($this->days);

        // Get subjects for this class (from ClassSubjectSettings or fallback)
        $subjectSettings = $this->getSubjectsForClass($class);

        if ($subjectSettings->isEmpty()) {
            $this->warnings[] = "No subjects found for {$class->full_name} (class range: {$classRange})";

            return;
        }

        // Initialize empty timetable [day][period] => null
        $timetable = [];
        for ($dayIndex = 0; $dayIndex < $daysCount; $dayIndex++) {
            $timetable[$dayIndex] = array_fill(1, $periodsPerDay, null);
        }

        // Load existing slots (combined periods) into timetable
        $existingSlots = TimetableSlot::where('class_room_id', $class->id)
            ->where('academic_term_id', $term->id)
            ->get();

        foreach ($existingSlots as $slot) {
            $timetable[$slot->day][$slot->period] = [
                'subject_id' => $slot->subject_id,
                'teacher_id' => $slot->teacher_id,
            ];
            // Mark teacher as occupied globally
            if ($slot->teacher_id) {
                $this->markTeacherOccupiedGlobal($slot->teacher_id, $slot->day, $slot->period, $class->id);
            }
        }

        // Track periods assigned per subject
        $assignedPeriods = [];
        foreach ($subjectSettings as $setting) {
            $subjectId = $setting->subject_id ?? $setting->subject->id;
            $assignedPeriods[$subjectId] = 0;
        }

        // Categorize subjects by type
        $compulsory = [];
        $optional = [];
        $eca = [];
        $ecaSubjectIds = [];

        foreach ($subjectSettings as $setting) {
            $subject = $setting->subject ?? Subject::find($setting->subject_id);
            $type = $subject->type ?? 'core';

            $item = [
                'setting' => $setting,
                'subject' => $subject,
                'min' => $setting->min_periods_per_week ?? $subject->min_periods_per_week ?? 1,
                'max' => $setting->max_periods_per_week ?? $subject->max_periods_per_week ?? 6,
            ];

            if (in_array($type, ['eca', 'co_curricular', 'extra_curricular'])) {
                $eca[] = $item;
                $ecaSubjectIds[] = $subject->id;
            } elseif (in_array($type, ['optional', 'elective'])) {
                $optional[] = $item;
            } else {
                $compulsory[] = $item;
            }
        }

        // Sort compulsory by min periods (higher first)
        usort($compulsory, fn ($a, $b) => $b['min'] - $a['min']);

        // Create positional template for consistency across days
        $template = $this->createDailyTemplate($compulsory, $periodsPerDay);

        // === PHASE 1: Assign compulsory subjects with positional consistency ===
        $this->assignSubjectsPhaseWithTemplate(
            $timetable, $assignedPeriods, $compulsory, $classRange, $class->id,
            $periodsPerDay, $daysCount, $ecaSubjectIds, 'min', $template
        );

        // === PHASE 2: Assign ECA subjects with special rules ===
        $this->assignEcaSubjects(
            $timetable, $assignedPeriods, $eca, $classRange, $class->id,
            $periodsPerDay, $daysCount, $ecaSubjectIds
        );

        // === PHASE 3: Assign optional subjects ===
        $this->assignSubjectsPhase(
            $timetable, $assignedPeriods, $optional, $classRange, $class->id,
            $periodsPerDay, $daysCount, $ecaSubjectIds, 'min'
        );

        // === PHASE 4: Fill up to max for all subjects ===
        $allSubjects = array_merge($compulsory, $optional, $eca);
        $this->assignSubjectsPhase(
            $timetable, $assignedPeriods, $allSubjects, $classRange, $class->id,
            $periodsPerDay, $daysCount, $ecaSubjectIds, 'max'
        );

        // === PHASE 5: Fill remaining empty slots ===
        $this->fillRemainingSlots(
            $timetable, $assignedPeriods, $allSubjects, $classRange, $class->id,
            $periodsPerDay, $daysCount, $ecaSubjectIds
        );

        // === PHASE 6: Validate full allocation ===
        $this->validateFullAllocation($timetable, $periodsPerDay, $daysCount);

        // Warn about under-allocation
        foreach ($allSubjects as $item) {
            $subjectId = $item['subject']->id;
            $assigned = $assignedPeriods[$subjectId] ?? 0;
            if ($assigned < $item['min']) {
                $this->warnings[] = "Could only assign {$assigned}/{$item['min']} minimum periods for {$item['subject']->name} in {$class->full_name}";
            }
        }

        // Now save all slots to database
        $this->saveTimetable($class, $term, $timetable, $periodsPerDay, $daysCount);
    }

    /**
     * Assign subjects phase (compulsory or optional)
     */
    private function assignSubjectsPhase(
        array &$timetable,
        array &$assignedPeriods,
        array $subjects,
        string $classRange,
        int $classId,
        int $periodsPerDay,
        int $daysCount,
        array $ecaSubjectIds,
        string $mode = 'min'
    ): void {
        foreach ($subjects as $item) {
            $subject = $item['subject'];
            $target = ($mode === 'min') ? $item['min'] : $item['max'];
            $teacher = $this->getTeacherForSubject($subject, $classId);

            while (($assignedPeriods[$subject->id] ?? 0) < $target) {
                $assigned = false;

                for ($dayIndex = 0; $dayIndex < $daysCount && ! $assigned; $dayIndex++) {
                    for ($period = 1; $period <= $periodsPerDay && ! $assigned; $period++) {
                        if ($timetable[$dayIndex][$period] !== null) {
                            continue;
                        }

                        if (! $this->canAssignSlot($timetable, $dayIndex, $period, $subject, $teacher, $classId, $ecaSubjectIds, $periodsPerDay)) {
                            continue;
                        }

                        // Soft constraint: try to avoid consecutive heavy subjects
                        if ($this->hasConsecutiveHeavySubject($timetable, $dayIndex, $period, $subject)) {
                            // Skip for now, but continue if no other option
                            continue;
                        }

                        $timetable[$dayIndex][$period] = [
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher?->id,
                        ];
                        $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;

                        if ($teacher) {
                            $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            $this->markTeacherOccupiedGlobal($teacher->id, $dayIndex, $period, $classId);
                        }
                        $assigned = true;
                    }
                }

                // If couldn't avoid consecutive heavy, try again allowing it
                if (! $assigned) {
                    for ($dayIndex = 0; $dayIndex < $daysCount && ! $assigned; $dayIndex++) {
                        for ($period = 1; $period <= $periodsPerDay && ! $assigned; $period++) {
                            if ($timetable[$dayIndex][$period] !== null) {
                                continue;
                            }

                            if (! $this->canAssignSlot($timetable, $dayIndex, $period, $subject, $teacher, $classId, $ecaSubjectIds, $periodsPerDay)) {
                                continue;
                            }

                            $timetable[$dayIndex][$period] = [
                                'subject_id' => $subject->id,
                                'teacher_id' => $teacher?->id,
                            ];
                            $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;

                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                                $this->markTeacherOccupiedGlobal($teacher->id, $dayIndex, $period, $classId);
                            }
                            $assigned = true;
                        }
                    }
                }

                if (! $assigned) {
                    break;
                }
            }
        }
    }

    /**
     * Assign subjects with positional template for consistency across days
     * Uses template to place core subjects in same positions each day
     */
    private function assignSubjectsPhaseWithTemplate(
        array &$timetable,
        array &$assignedPeriods,
        array $subjects,
        string $classRange,
        int $classId,
        int $periodsPerDay,
        int $daysCount,
        array $ecaSubjectIds,
        string $mode = 'min',
        array $template = []
    ): void {
        foreach ($subjects as $item) {
            $subject = $item['subject'];
            $target = ($mode === 'min') ? $item['min'] : $item['max'];
            $teacher = $this->getTeacherForSubject($subject, $classId);

            // Get preferred period from template
            $preferredPeriod = $template[$subject->id] ?? null;

            while (($assignedPeriods[$subject->id] ?? 0) < $target) {
                $assigned = false;

                for ($dayIndex = 0; $dayIndex < $daysCount && ! $assigned; $dayIndex++) {
                    // Build period order: preferred first, then others
                    $periodsToTry = [];
                    if ($preferredPeriod !== null && $preferredPeriod >= 1 && $preferredPeriod <= $periodsPerDay) {
                        $periodsToTry[] = $preferredPeriod;
                    }
                    for ($p = 1; $p <= $periodsPerDay; $p++) {
                        if (! in_array($p, $periodsToTry)) {
                            $periodsToTry[] = $p;
                        }
                    }

                    foreach ($periodsToTry as $period) {
                        if ($assigned) {
                            break;
                        }
                        if ($timetable[$dayIndex][$period] !== null) {
                            continue;
                        }

                        if (! $this->canAssignSlot($timetable, $dayIndex, $period, $subject, $teacher, $classId, $ecaSubjectIds, $periodsPerDay)) {
                            continue;
                        }

                        // Soft constraint: avoid consecutive heavy subjects
                        if ($this->hasConsecutiveHeavySubject($timetable, $dayIndex, $period, $subject)) {
                            continue;
                        }

                        $timetable[$dayIndex][$period] = [
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher?->id,
                        ];
                        $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;

                        if ($teacher) {
                            $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            $this->markTeacherOccupiedGlobal($teacher->id, $dayIndex, $period, $classId);
                        }
                        $assigned = true;
                    }
                }

                // Fallback: allow consecutive heavy if no other option
                if (! $assigned) {
                    for ($dayIndex = 0; $dayIndex < $daysCount && ! $assigned; $dayIndex++) {
                        for ($period = 1; $period <= $periodsPerDay && ! $assigned; $period++) {
                            if ($timetable[$dayIndex][$period] !== null) {
                                continue;
                            }

                            if (! $this->canAssignSlot($timetable, $dayIndex, $period, $subject, $teacher, $classId, $ecaSubjectIds, $periodsPerDay)) {
                                continue;
                            }

                            $timetable[$dayIndex][$period] = [
                                'subject_id' => $subject->id,
                                'teacher_id' => $teacher?->id,
                            ];
                            $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;

                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                                $this->markTeacherOccupiedGlobal($teacher->id, $dayIndex, $period, $classId);
                            }
                            $assigned = true;
                        }
                    }
                }

                if (! $assigned) {
                    break;
                }
            }
        }
    }

    /**
     * Assign ECA subjects with special rules (one type per day, consecutive if 2)
     */
    private function assignEcaSubjects(
        array &$timetable,
        array &$assignedPeriods,
        array $ecaItems,
        string $classRange,
        int $classId,
        int $periodsPerDay,
        int $daysCount,
        array $ecaSubjectIds
    ): void {
        foreach ($ecaItems as $item) {
            $subject = $item['subject'];
            $target = $item['min'];
            $teacher = $this->getTeacherForSubject($subject, $classId);

            while (($assignedPeriods[$subject->id] ?? 0) < $target) {
                $assigned = false;

                // Prefer middle/last periods
                $periodsToTry = array_merge($this->preferredEcaPeriods, range(1, $periodsPerDay));
                $periodsToTry = array_unique(array_filter($periodsToTry, fn ($p) => $p <= $periodsPerDay));

                for ($dayIndex = 0; $dayIndex < $daysCount && ! $assigned; $dayIndex++) {
                    // Check if another ECA is already on this day
                    $existingEca = $this->getEcaSubjectOnDay($timetable, $dayIndex, $ecaSubjectIds);
                    if ($existingEca !== null && $existingEca !== $subject->id) {
                        continue; // Different ECA on this day, skip
                    }

                    foreach ($periodsToTry as $period) {
                        if ($timetable[$dayIndex][$period] !== null) {
                            continue;
                        }

                        if (! $this->canAssignSlot($timetable, $dayIndex, $period, $subject, $teacher, $classId, $ecaSubjectIds, $periodsPerDay)) {
                            continue;
                        }

                        $timetable[$dayIndex][$period] = [
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher?->id,
                        ];
                        $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;

                        if ($teacher) {
                            $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            $this->markTeacherOccupiedGlobal($teacher->id, $dayIndex, $period, $classId);
                        }
                        $assigned = true;
                        break;
                    }
                }

                if (! $assigned) {
                    break;
                }
            }
        }
    }

    /**
     * Fill remaining empty slots
     */
    private function fillRemainingSlots(
        array &$timetable,
        array &$assignedPeriods,
        array $allSubjects,
        string $classRange,
        int $classId,
        int $periodsPerDay,
        int $daysCount,
        array $ecaSubjectIds
    ): void {
        for ($dayIndex = 0; $dayIndex < $daysCount; $dayIndex++) {
            for ($period = 1; $period <= $periodsPerDay; $period++) {
                if ($timetable[$dayIndex][$period] !== null) {
                    continue;
                }

                // Find best subject that can fill this slot
                foreach ($allSubjects as $item) {
                    $subject = $item['subject'];
                    $teacher = $this->getTeacherForSubject($subject, $classId);

                    // Skip if at max
                    if (($assignedPeriods[$subject->id] ?? 0) >= $item['max']) {
                        continue;
                    }

                    if (! $this->canAssignSlot($timetable, $dayIndex, $period, $subject, $teacher, $classId, $ecaSubjectIds, $periodsPerDay)) {
                        continue;
                    }

                    $timetable[$dayIndex][$period] = [
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher?->id,
                    ];
                    $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;

                    if ($teacher) {
                        $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                        $this->markTeacherOccupiedGlobal($teacher->id, $dayIndex, $period, $classId);
                    }
                    break;
                }
            }
        }
    }

    /**
     * Save generated timetable to database
     */
    private function saveTimetable(ClassRoom $class, AcademicTerm $term, array $timetable, int $periodsPerDay, int $daysCount): void
    {
        // Get existing slot IDs to avoid duplicates
        $existingSlots = TimetableSlot::where('class_room_id', $class->id)
            ->where('academic_term_id', $term->id)
            ->get()
            ->keyBy(function ($slot) {
                return "{$slot->day}_{$slot->period}";
            });

        for ($dayIndex = 0; $dayIndex < $daysCount; $dayIndex++) {
            for ($period = 1; $period <= $periodsPerDay; $period++) {
                $slot = $timetable[$dayIndex][$period] ?? null;
                $key = "{$dayIndex}_{$period}";

                // Skip if already exists (combined periods)
                if ($existingSlots->has($key)) {
                    continue;
                }

                // Skip empty slots
                if ($slot === null) {
                    continue;
                }

                TimetableSlot::create([
                    'class_room_id' => $class->id,
                    'subject_id' => $slot['subject_id'],
                    'teacher_id' => $slot['teacher_id'],
                    'day' => $dayIndex,
                    'period' => $period,
                    'is_combined' => false,
                    'academic_term_id' => $term->id,
                ]);
            }
        }
    }

    /**
     * Generate statistics about the timetable
     */
    private function generateStatistics(array $classIds, int $termId): array
    {
        $totalSlots = TimetableSlot::whereIn('class_room_id', $classIds)
            ->where('academic_term_id', $termId)
            ->count();

        $combinedSlots = TimetableSlot::whereIn('class_room_id', $classIds)
            ->where('academic_term_id', $termId)
            ->where('is_combined', true)
            ->count();

        $teachersUsed = TimetableSlot::whereIn('class_room_id', $classIds)
            ->where('academic_term_id', $termId)
            ->distinct('teacher_id')
            ->count('teacher_id');

        return [
            'total_slots' => $totalSlots,
            'combined_slots' => $combinedSlots,
            'regular_slots' => $totalSlots - $combinedSlots,
            'teachers_used' => $teachersUsed,
            'classes_generated' => count($classIds),
        ];
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
