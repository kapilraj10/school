<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\CombinedPeriod;
use App\Models\Constraint;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableGeneratorService
{
    private const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];

    private const PERIODS_PER_DAY = 8;
    private const MAX_SUBJECT_CONSECUTIVE = 1; // Max consecutive periods for same subject
    private const MAX_SUBJECT_PER_DAY = 2; // Max periods per subject per day

    private array $errors = [];
    private array $warnings = [];
    private Collection $constraints;
    private array $teacherSchedule = []; // [teacher_id][day][period] => slot_info
    private array $subjectDistribution = []; // [class_id][subject_id] => periods_assigned
    private array $classSchedule = []; // [class_id][day][period] => slot_info

    public function __construct()
    {
        $this->constraints = Constraint::active()->orderBy('priority', 'desc')->get();
    }

    /**
     * Main generation method
     */
    public function generate(array $classIds, int $termId, array $options = []): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->teacherSchedule = [];
        $this->subjectDistribution = [];
        $this->classSchedule = [];

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

            // Step 2: Generate for each class using improved algorithm
            foreach ($classes as $class) {
                $this->generateForClassImproved($class, $term, $options);
            }

            // Step 3: Validate all constraints
            $conflicts = $this->validateNoConflicts($classIds, $termId);

            if (!empty($conflicts)) {
                $this->errors = array_merge($this->errors, $conflicts);
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
            
            // Skip first period (period 1) for combined classes
            if ($combined->period === 1) {
                $this->warnings[] = "Combined period '{$combined->name}' cannot be in first period. Skipped.";
                continue;
            }

            foreach ($classIds as $classId) {
                if (!in_array($classId, $classes->pluck('id')->toArray())) {
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
            }
        }
    }

    /**
     * Generate timetable for a single class using improved algorithm
     * Based on the sophisticated algorithm from Algorithm.php
     */
    private function generateForClassImproved(ClassRoom $class, AcademicTerm $term, array $options): void
    {
        // Initialize class schedule tracker
        $this->classSchedule[$class->id] = [];
        for ($day = 0; $day < 6; $day++) {
            $this->classSchedule[$class->id][$day] = array_fill(1, self::PERIODS_PER_DAY, null);
        }

        // Get existing slots (combined periods)
        $existingSlots = TimetableSlot::where('class_room_id', $class->id)
            ->where('academic_term_id', $term->id)
            ->get();

        // Mark existing slots in schedule
        foreach ($existingSlots as $slot) {
            $this->classSchedule[$class->id][$slot->day][$slot->period] = [
                'subject_id' => $slot->subject_id,
                'teacher_id' => $slot->teacher_id,
                'is_combined' => $slot->is_combined,
            ];
        }

        // Get applicable subjects for this class level
        $subjects = Subject::active()
            ->forLevel($class->level)
            ->get();

        // Sort subjects by priority (core subjects first, then by weekly_periods desc)
        $subjects = $subjects->sort(function($a, $b) {
            $priorityA = $this->getSubjectPriority($a);
            $priorityB = $this->getSubjectPriority($b);
            
            if ($priorityA !== $priorityB) {
                return $priorityA - $priorityB;
            }
            
            return $b->weekly_periods <=> $a->weekly_periods;
        })->values();

        // Initialize subject distribution tracker
        $this->subjectDistribution[$class->id] = [];
        foreach ($subjects as $subject) {
            $this->subjectDistribution[$class->id][$subject->id] = 0;
        }

        // === PHASE 1: Assign minimum periods to all subjects ===
        foreach ($subjects as $subject) {
            $minPeriods = $this->getMinPeriodsPerWeek($subject);
            $assigned = $this->assignPeriodsForSubject(
                $class,
                $subject,
                $term,
                $minPeriods,
                $options
            );

            if ($assigned < $minPeriods) {
                $this->warnings[] = "Could only assign {$assigned}/{$minPeriods} minimum periods for {$subject->name} in {$class->full_name}";
            }
        }

        // === PHASE 2: Fill remaining slots up to max periods ===
        foreach ($subjects as $subject) {
            $maxPeriods = $this->getMaxPeriodsPerWeek($subject);
            $currentAssigned = $this->subjectDistribution[$class->id][$subject->id];
            
            if ($currentAssigned < $maxPeriods) {
                $additionalNeeded = $maxPeriods - $currentAssigned;
                $assigned = $this->assignPeriodsForSubject(
                    $class,
                    $subject,
                    $term,
                    $additionalNeeded,
                    $options,
                    false // Don't be strict in phase 2
                );
            }
        }

        // === PHASE 3: Fill any remaining empty slots ===
        $this->fillRemainingSlots($class, $subjects, $term, $options);
    }

    /**
     * Get subject priority for sorting (lower is higher priority)
     */
    private function getSubjectPriority(Subject $subject): int
    {
        return match($subject->type) {
            'core' => 0,
            'compulsory' => 0,
            'elective' => 1,
            'co_curricular' => 2,
            default => 3,
        };
    }

    /**
     * Get minimum periods per week for a subject
     */
    private function getMinPeriodsPerWeek(Subject $subject): int
    {
        // Check if subject has min_periods_per_week attribute
        if (isset($subject->min_periods_per_week)) {
            return $subject->min_periods_per_week;
        }
        
        // Fallback to weekly_periods or default based on type
        return $subject->weekly_periods ?? ($subject->type === 'core' ? 4 : 2);
    }

    /**
     * Get maximum periods per week for a subject
     */
    private function getMaxPeriodsPerWeek(Subject $subject): int
    {
        // Check if subject has max_periods_per_week attribute
        if (isset($subject->max_periods_per_week)) {
            return $subject->max_periods_per_week;
        }
        
        // Fallback to weekly_periods or slightly higher
        $base = $subject->weekly_periods ?? 4;
        return $base + 1; // Allow one extra period if slots available
    }

    /**
     * Assign periods for a specific subject
     */
    private function assignPeriodsForSubject(
        ClassRoom $class,
        Subject $subject,
        AcademicTerm $term,
        int $periodsNeeded,
        array $options,
        bool $strict = true
    ): int {
        $periodsAssigned = 0;
        $respectTeacherAvail = $options['respect_teacher_availability'] ?? true;
        $avoidConsecutive = $options['avoid_consecutive_subjects'] ?? true;
        $balanceDailyLoad = $options['balance_daily_load'] ?? true;

        // Get days in optimal order (distribute throughout week)
        $days = $balanceDailyLoad ? $this->getOptimalDayOrder($class) : range(0, 5);

        foreach ($days as $day) {
            if ($periodsAssigned >= $periodsNeeded) {
                break;
            }

            // Check how many periods of this subject already assigned today
            $todayCount = $this->countSubjectPeriodsOnDay($class->id, $subject->id, $day);
            
            if ($todayCount >= self::MAX_SUBJECT_PER_DAY && $strict) {
                continue; // Limit subject occurrences per day
            }

            // Find available slots for this day
            for ($period = 1; $period <= self::PERIODS_PER_DAY; $period++) {
                if ($periodsAssigned >= $periodsNeeded) {
                    break;
                }

                // Check if slot is empty
                if ($this->classSchedule[$class->id][$day][$period] !== null) {
                    continue;
                }

                // Check consecutive subjects if option enabled
                if ($avoidConsecutive && $this->wouldBeConsecutive($class->id, $subject->id, $day, $period)) {
                    continue;
                }

                // Find available teacher
                $teacher = $this->findAvailableTeacherImproved(
                    $subject,
                    $day,
                    $period,
                    $term->id,
                    $respectTeacherAvail
                );

                if (!$teacher) {
                    if ($strict) {
                        continue; // Skip this slot if no teacher available
                    }
                    // In non-strict mode, we might want to assign anyway
                    $this->warnings[] = "No available teacher for {$subject->name} in {$class->full_name} on day $day period $period";
                    continue;
                }

                // Create the slot
                $slot = TimetableSlot::create([
                    'class_room_id' => $class->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'day' => $day,
                    'period' => $period,
                    'is_combined' => false,
                    'academic_term_id' => $term->id,
                ]);

                // Update tracking structures
                $this->classSchedule[$class->id][$day][$period] = [
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'is_combined' => false,
                ];

                $this->trackTeacherAssignment($teacher->id, $day, $period, $slot);
                $this->subjectDistribution[$class->id][$subject->id]++;
                $periodsAssigned++;
            }
        }

        return $periodsAssigned;
    }

    /**
     * Get optimal day order for balanced distribution
     */
    private function getOptimalDayOrder(ClassRoom $class): array
    {
        // Calculate current load per day
        $dailyLoad = [];
        for ($day = 0; $day < 6; $day++) {
            $count = 0;
            foreach ($this->classSchedule[$class->id][$day] as $slot) {
                if ($slot !== null) {
                    $count++;
                }
            }
            $dailyLoad[$day] = $count;
        }

        // Sort days by load (least loaded first)
        asort($dailyLoad);
        return array_keys($dailyLoad);
    }

    /**
     * Count how many periods of a subject are already on a specific day
     */
    private function countSubjectPeriodsOnDay(int $classId, int $subjectId, int $day): int
    {
        $count = 0;
        foreach ($this->classSchedule[$classId][$day] as $slot) {
            if ($slot && $slot['subject_id'] === $subjectId) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check if assigning this subject would create consecutive periods
     */
    private function wouldBeConsecutive(int $classId, int $subjectId, int $day, int $period): bool
    {
        // Check previous period
        if ($period > 1) {
            $prevSlot = $this->classSchedule[$classId][$day][$period - 1];
            if ($prevSlot && $prevSlot['subject_id'] === $subjectId) {
                // Check if we already have max consecutive
                $consecutiveCount = 1;
                for ($p = $period - 2; $p >= 1; $p--) {
                    $slot = $this->classSchedule[$classId][$day][$p];
                    if ($slot && $slot['subject_id'] === $subjectId) {
                        $consecutiveCount++;
                    } else {
                        break;
                    }
                }
                if ($consecutiveCount >= self::MAX_SUBJECT_CONSECUTIVE) {
                    return true;
                }
            }
        }

        // Check next period
        if ($period < self::PERIODS_PER_DAY) {
            $nextSlot = $this->classSchedule[$classId][$day][$period + 1];
            if ($nextSlot && $nextSlot['subject_id'] === $subjectId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find an available teacher with improved logic
     */
    private function findAvailableTeacherImproved(
        Subject $subject,
        int $day,
        int $period,
        int $termId,
        bool $respectAvailability = true
    ): ?Teacher {
        // Get teachers who can teach this subject
        $teachers = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->get();

        foreach ($teachers as $teacher) {
            // Check teacher availability if option enabled
            if ($respectAvailability && !$teacher->isAvailable($day, $period)) {
                continue;
            }

            // Check if teacher is already assigned at this time (in our tracking)
            if (isset($this->teacherSchedule[$teacher->id][$day][$period])) {
                continue;
            }

            // Check daily limit
            $dailyPeriods = count($this->teacherSchedule[$teacher->id][$day] ?? []);
            if ($dailyPeriods >= $teacher->max_periods_per_day) {
                continue;
            }

            return $teacher;
        }

        return null;
    }

    /**
     * Fill remaining empty slots with any available subjects
     */
    private function fillRemainingSlots(ClassRoom $class, Collection $subjects, AcademicTerm $term, array $options): void
    {
        $respectTeacherAvail = $options['respect_teacher_availability'] ?? true;
        $subjectIndex = 0;

        for ($day = 0; $day < 6; $day++) {
            for ($period = 1; $period <= self::PERIODS_PER_DAY; $period++) {
                // Check if slot is empty
                if ($this->classSchedule[$class->id][$day][$period] !== null) {
                    continue;
                }

                // Try to find any available subject/teacher combination
                $attempts = 0;
                while ($attempts < $subjects->count()) {
                    $subject = $subjects[$subjectIndex % $subjects->count()];
                    
                    // Find teacher
                    $teacher = $this->findAvailableTeacherImproved(
                        $subject,
                        $day,
                        $period,
                        $term->id,
                        $respectTeacherAvail
                    );

                    if ($teacher) {
                        // Create the slot
                        $slot = TimetableSlot::create([
                            'class_room_id' => $class->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'day' => $day,
                            'period' => $period,
                            'is_combined' => false,
                            'academic_term_id' => $term->id,
                        ]);

                        // Update tracking
                        $this->classSchedule[$class->id][$day][$period] = [
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'is_combined' => false,
                        ];

                        $this->trackTeacherAssignment($teacher->id, $day, $period, $slot);
                        $this->subjectDistribution[$class->id][$subject->id]++;
                        
                        $subjectIndex++;
                        break;
                    }

                    $subjectIndex++;
                    $attempts++;
                }
            }
        }
    }

    /**
     * Find an available teacher for subject at given time
     */
    private function findAvailableTeacher(Subject $subject, int $day, int $period, int $termId): ?Teacher
    {
        // Get teachers who can teach this subject (from subject_ids JSON column)
        $teachers = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->get();

        foreach ($teachers as $teacher) {
            // Check availability
            if (!$teacher->isAvailable($day, $period)) {
                continue;
            }

            // Check if teacher is already assigned at this time
            $conflict = TimetableSlot::where('teacher_id', $teacher->id)
                ->where('academic_term_id', $termId)
                ->where('day', $day)
                ->where('period', $period)
                ->exists();

            if ($conflict) {
                continue;
            }

            // Check daily limit
            $dailyPeriods = TimetableSlot::where('teacher_id', $teacher->id)
                ->where('academic_term_id', $termId)
                ->where('day', $day)
                ->count();
            
            if ($dailyPeriods >= $teacher->max_periods_per_day) {
                continue;
            }

            return $teacher;
        }

        return null;
    }

    /**
     * Track teacher assignment
     */
    private function trackTeacherAssignment(int $teacherId, int $day, int $period, $slotInfo = null): void
    {
        if (!isset($this->teacherSchedule[$teacherId])) {
            $this->teacherSchedule[$teacherId] = [];
        }

        if (!isset($this->teacherSchedule[$teacherId][$day])) {
            $this->teacherSchedule[$teacherId][$day] = [];
        }

        $this->teacherSchedule[$teacherId][$day][$period] = $slotInfo;
    }

    /**
     * Validate no conflicts in generated timetable
     */
    private function validateNoConflicts(array $classIds, int $termId): array
    {
        $conflicts = [];

        // Check for teacher conflicts (same teacher, same time, different class)
        $teacherConflicts = DB::table('timetable_slots as t1')
            ->join('timetable_slots as t2', function($join) use ($termId) {
                $join->on('t1.teacher_id', '=', 't2.teacher_id')
                    ->on('t1.day', '=', 't2.day')
                    ->on('t1.period', '=', 't2.period')
                    ->where('t1.academic_term_id', $termId)
                    ->where('t2.academic_term_id', $termId)
                    ->whereColumn('t1.id', '!=', 't2.id');
            })
            ->whereIn('t1.class_room_id', $classIds)
            ->select('t1.teacher_id', 't1.day', 't1.period')
            ->distinct()
            ->get();

        foreach ($teacherConflicts as $conflict) {
            $teacher = Teacher::find($conflict->teacher_id);
            // $dayName = TimetableSlot::$days[$conflict->day];
            $dayName = self::DAYS[$conflict->day] ?? "Day {$conflict->day}";
            $conflicts[] = "Teacher {$teacher->name} has conflicting classes on {$dayName} period {$conflict->period}";
        }

        return $conflicts;
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