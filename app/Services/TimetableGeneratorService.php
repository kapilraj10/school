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
        // First, try to get from ClassSubjectSettings
        $classSettings = ClassSubjectSetting::with('subject')
            ->where('class_room_id', $class->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        if ($classSettings->isNotEmpty()) {
            return $classSettings;
        }

        // Fallback: get subjects by class range
        $classNumber = $this->extractClassNumber($class->name);
        $classRange = $this->getClassRangeForClassNumber($classNumber);

        return Subject::where('class_range', $classRange)
            ->where('status', 'active')
            ->get()
            ->map(function ($subject) {
                // Convert to a pseudo-ClassSubjectSetting format for consistency
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

    /**
     * Get teacher for a subject and class range
     * Finds a teacher who teaches this subject for the given class range
     */
    private function getTeacherForSubject(Subject $subject, string $classRange): ?Teacher
    {
        // Find teacher who can teach this subject
        $teachers = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->get();

        // Prefer teachers with matching class range (by subject association)
        foreach ($teachers as $teacher) {
            return $teacher;
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

        try {
            DB::beginTransaction();

            // Clear existing timetable for these classes if option is set
            if ($options['clear_existing'] ?? true) {
                TimetableSlot::whereIn('class_room_id', $classIds)
                    ->where('academic_term_id', $termId)
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
     * Generate timetable for a single class - uses ClassSubjectSettings if available
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
        }

        // Sort by priority (higher priority first)
        $subjectSettings = $subjectSettings->sortByDesc(function ($item) {
            return $item->priority ?? 5;
        })->values();

        // Track periods assigned per subject
        $assignedPeriods = [];
        foreach ($subjectSettings as $setting) {
            $subjectId = $setting->subject_id ?? $setting->subject->id;
            $assignedPeriods[$subjectId] = 0;
        }

        // === FIRST PASS: Assign minimum periods to all subjects ===
        foreach ($subjectSettings as $setting) {
            $subject = $setting->subject ?? Subject::find($setting->subject_id);
            $subjectId = $subject->id;

            $targetPeriods = $setting->min_periods_per_week ?? $subject->min_periods_per_week ?? 1;
            $teacher = $this->getTeacherForSubject($subject, $classRange);

            while ($assignedPeriods[$subjectId] < $targetPeriods) {
                $assigned = false;

                for ($dayIndex = 0; $dayIndex < $daysCount; $dayIndex++) {
                    if ($assignedPeriods[$subjectId] >= $targetPeriods) {
                        break;
                    }

                    $dayName = $this->dayIndexToName[$dayIndex] ?? 'Monday';

                    // Check if subject already assigned today (use setting from DB)
                    $todayCount = 0;
                    foreach ($timetable[$dayIndex] as $slot) {
                        if ($slot && $slot['subject_id'] === $subjectId) {
                            $todayCount++;
                        }
                    }

                    if ($todayCount >= $this->maxSameSubjectPerDay) {
                        continue;
                    }

                    // Find empty slot where teacher is available
                    for ($period = 1; $period <= $periodsPerDay; $period++) {
                        if ($timetable[$dayIndex][$period] === null) {
                            // Check teacher availability
                            if ($teacher) {
                                if (! $this->isTeacherAvailable($teacher, $dayName, $period)) {
                                    continue; // Teacher not available, try next slot
                                }
                                if ($this->isTeacherBusy($teacher->id, $dayIndex, $period)) {
                                    continue; // Teacher already assigned, try next slot
                                }
                            }

                            // Assign this slot
                            $timetable[$dayIndex][$period] = [
                                'subject_id' => $subjectId,
                                'teacher_id' => $teacher?->id,
                            ];

                            // Mark teacher as busy
                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            }

                            $assignedPeriods[$subjectId]++;
                            $assigned = true;
                            break;
                        }
                    }
                }

                if (! $assigned) {
                    break; // No more slots available
                }
            }

            // Warn if couldn't meet minimum
            if ($assignedPeriods[$subjectId] < $targetPeriods) {
                $reason = $teacher ? 'teacher constraints or no available slots' : 'no teacher assigned';
                $this->warnings[] = "Could only assign {$assignedPeriods[$subjectId]}/{$targetPeriods} minimum periods for {$subject->name} in {$class->full_name} — {$reason}";
            }
        }

        // === SECOND PASS: Fill remaining slots up to max_period_per_week ===
        foreach ($subjectSettings as $setting) {
            $subject = $setting->subject ?? Subject::find($setting->subject_id);
            $subjectId = $subject->id;

            $maxPeriods = $setting->max_periods_per_week ?? $subject->max_periods_per_week ?? 6;
            $teacher = $this->getTeacherForSubject($subject, $classRange);

            while ($assignedPeriods[$subjectId] < $maxPeriods) {
                $assigned = false;

                for ($dayIndex = 0; $dayIndex < $daysCount; $dayIndex++) {
                    if ($assignedPeriods[$subjectId] >= $maxPeriods) {
                        break;
                    }

                    $dayName = $this->dayIndexToName[$dayIndex] ?? 'Monday';

                    // Check if subject already assigned today (limit per day from settings)
                    $todayCount = 0;
                    foreach ($timetable[$dayIndex] as $slot) {
                        if ($slot && $slot['subject_id'] === $subjectId) {
                            $todayCount++;
                        }
                    }

                    if ($todayCount >= $this->maxSameSubjectPerDay) {
                        continue;
                    }

                    // Find empty slot where teacher is available
                    for ($period = 1; $period <= $periodsPerDay; $period++) {
                        if ($timetable[$dayIndex][$period] === null) {
                            // Check teacher availability
                            if ($teacher) {
                                if (! $this->isTeacherAvailable($teacher, $dayName, $period)) {
                                    continue;
                                }
                                if ($this->isTeacherBusy($teacher->id, $dayIndex, $period)) {
                                    continue;
                                }
                            }

                            $timetable[$dayIndex][$period] = [
                                'subject_id' => $subjectId,
                                'teacher_id' => $teacher?->id,
                            ];

                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            }

                            $assignedPeriods[$subjectId]++;
                            $assigned = true;
                            break;
                        }
                    }
                }

                if (! $assigned) {
                    break; // No more slots available for this subject
                }
            }
        }

        // === THIRD PASS: Fill any remaining empty slots (remove per day limit) ===
        $settingsList = $subjectSettings->values()->all();
        $subjectIndex = 0;

        for ($dayIndex = 0; $dayIndex < $daysCount; $dayIndex++) {
            $dayName = $this->dayIndexToName[$dayIndex] ?? 'Monday';

            for ($period = 1; $period <= $periodsPerDay; $period++) {
                if ($timetable[$dayIndex][$period] === null) {
                    // Try to find an available subject/teacher
                    $attempts = 0;
                    while ($attempts < count($settingsList)) {
                        $setting = $settingsList[$subjectIndex % count($settingsList)];
                        $subject = $setting->subject ?? Subject::find($setting->subject_id);
                        $teacher = $this->getTeacherForSubject($subject, $classRange);

                        // Check teacher availability
                        $teacherOk = true;
                        if ($teacher) {
                            if (! $this->isTeacherAvailable($teacher, $dayName, $period)) {
                                $teacherOk = false;
                            }
                            if ($this->isTeacherBusy($teacher->id, $dayIndex, $period)) {
                                $teacherOk = false;
                            }
                        }

                        if ($teacherOk) {
                            $timetable[$dayIndex][$period] = [
                                'subject_id' => $subject->id,
                                'teacher_id' => $teacher?->id,
                            ];

                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            }

                            $assignedPeriods[$subject->id] = ($assignedPeriods[$subject->id] ?? 0) + 1;
                            $subjectIndex++;
                            break;
                        }

                        $subjectIndex++;
                        $attempts++;
                    }
                }
            }
        }

        // Now save all slots to database
        $this->saveTimetable($class, $term, $timetable, $periodsPerDay, $daysCount);
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
