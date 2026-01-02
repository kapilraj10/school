<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableGeneratorService
{
    // Timetable configuration - matching Algorithm.php exactly
    private array $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    private int $periodsPerDay = 8;

    // Day name mapping for availability check - matching Algorithm.php
    private array $dayShortMap = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
    ];

    // Day index to name mapping
    private array $dayIndexToName = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];

    private array $errors = [];

    private array $warnings = [];

    private array $teacherSchedule = []; // [teacher_id][day][period] => true (busy)

    /**
     * Get class range for a class number - matching Algorithm.php exactly
     */
    private function getClassRangeForClassNumber(int $classNumber): string
    {
        if ($classNumber >= 1 && $classNumber <= 4) {
            return '1 - 4';
        }

        if ($classNumber >= 5 && $classNumber <= 7) {
            return '5 - 7';
        }

        if ($classNumber === 8) {
            return '8';
        }

        if ($classNumber >= 9 && $classNumber <= 10) {
            return '9 - 10';
        }

        return '1 - 4';
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
     * Get subjects for a specific class range - matching Algorithm.php
     */
    private function getSubjectsForClassRange(string $classRange): Collection
    {
        return Subject::where('class_range', $classRange)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get teacher for a subject and class range - matching Algorithm.php
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
     * Generate timetable for a single class - matching Algorithm.php generateTimetable() exactly
     */
    private function generateTimetableForClass(ClassRoom $class, AcademicTerm $term): void
    {
        $classNumber = $this->extractClassNumber($class->name);
        $classRange = $this->getClassRangeForClassNumber($classNumber);

        // Get subjects for this class range
        $subjects = $this->getSubjectsForClassRange($classRange);

        if ($subjects->isEmpty()) {
            $this->warnings[] = "No subjects found for {$class->full_name} (class range: {$classRange})";

            return;
        }

        // Initialize empty timetable [day][period] => null
        $timetable = [];
        for ($dayIndex = 0; $dayIndex < 6; $dayIndex++) {
            $timetable[$dayIndex] = array_fill(1, $this->periodsPerDay, null);
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

        // Sort subjects by priority (compulsory first) - matching Algorithm.php
        $subjects = $subjects->sort(function ($a, $b) {
            $priorityA = $a->type === 'compulsory' ? 0 : 1;
            $priorityB = $b->type === 'compulsory' ? 0 : 1;

            return $priorityA - $priorityB;
        })->values();

        // Track periods assigned per subject
        $assignedPeriods = [];
        foreach ($subjects as $subject) {
            $assignedPeriods[$subject->id] = 0;
        }

        // === FIRST PASS: Assign minimum periods to all subjects ===
        foreach ($subjects as $subject) {
            $targetPeriods = $subject->min_periods_per_week ?? $subject->weekly_periods ?? 4;
            $teacher = $this->getTeacherForSubject($subject, $classRange);

            while ($assignedPeriods[$subject->id] < $targetPeriods) {
                $assigned = false;

                for ($dayIndex = 0; $dayIndex < 6; $dayIndex++) {
                    if ($assignedPeriods[$subject->id] >= $targetPeriods) {
                        break;
                    }

                    $dayName = $this->dayIndexToName[$dayIndex];

                    // Check if subject already assigned today (limit 2 per day for main subjects)
                    $todayCount = 0;
                    foreach ($timetable[$dayIndex] as $slot) {
                        if ($slot && $slot['subject_id'] === $subject->id) {
                            $todayCount++;
                        }
                    }

                    if ($todayCount >= 2) {
                        continue;
                    }

                    // Find empty slot where teacher is available
                    for ($period = 1; $period <= $this->periodsPerDay; $period++) {
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
                                'subject_id' => $subject->id,
                                'teacher_id' => $teacher?->id,
                            ];

                            // Mark teacher as busy
                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            }

                            $assignedPeriods[$subject->id]++;
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
            if ($assignedPeriods[$subject->id] < $targetPeriods) {
                $this->warnings[] = "Could only assign {$assignedPeriods[$subject->id]}/{$targetPeriods} minimum periods for {$subject->name} in {$class->full_name}";
            }
        }

        // === SECOND PASS: Fill remaining slots up to max_period_per_week ===
        foreach ($subjects as $subject) {
            $maxPeriods = $subject->max_periods_per_week ?? ($subject->weekly_periods ?? 4) + 1;
            $teacher = $this->getTeacherForSubject($subject, $classRange);

            while ($assignedPeriods[$subject->id] < $maxPeriods) {
                $assigned = false;

                for ($dayIndex = 0; $dayIndex < 6; $dayIndex++) {
                    if ($assignedPeriods[$subject->id] >= $maxPeriods) {
                        break;
                    }

                    $dayName = $this->dayIndexToName[$dayIndex];

                    // Check if subject already assigned today (limit 2 per day)
                    $todayCount = 0;
                    foreach ($timetable[$dayIndex] as $slot) {
                        if ($slot && $slot['subject_id'] === $subject->id) {
                            $todayCount++;
                        }
                    }

                    if ($todayCount >= 2) {
                        continue;
                    }

                    // Find empty slot where teacher is available
                    for ($period = 1; $period <= $this->periodsPerDay; $period++) {
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
                                'subject_id' => $subject->id,
                                'teacher_id' => $teacher?->id,
                            ];

                            if ($teacher) {
                                $this->markTeacherBusy($teacher->id, $dayIndex, $period);
                            }

                            $assignedPeriods[$subject->id]++;
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

        // === THIRD PASS: Fill any remaining empty slots (remove 2 per day limit) ===
        $subjectIndex = 0;
        $subjectsList = $subjects->values()->all();

        for ($dayIndex = 0; $dayIndex < 6; $dayIndex++) {
            $dayName = $this->dayIndexToName[$dayIndex];

            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                if ($timetable[$dayIndex][$period] === null) {
                    // Try to find an available subject/teacher
                    $attempts = 0;
                    while ($attempts < count($subjectsList)) {
                        $subject = $subjectsList[$subjectIndex % count($subjectsList)];
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

                            $assignedPeriods[$subject->id]++;
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
        $this->saveTimetable($class, $term, $timetable);
    }

    /**
     * Save generated timetable to database
     */
    private function saveTimetable(ClassRoom $class, AcademicTerm $term, array $timetable): void
    {
        // Get existing slot IDs to avoid duplicates
        $existingSlots = TimetableSlot::where('class_room_id', $class->id)
            ->where('academic_term_id', $term->id)
            ->get()
            ->keyBy(function ($slot) {
                return "{$slot->day}_{$slot->period}";
            });

        for ($dayIndex = 0; $dayIndex < 6; $dayIndex++) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                $slot = $timetable[$dayIndex][$period];
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
