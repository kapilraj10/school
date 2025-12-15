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

    private array $errors = [];
    private array $warnings = [];
    private Collection $constraints;
    private array $teacherSchedule = []; // [teacher_id][day] => period_count
    private array $subjectDistribution = []; // [class_id][subject_id] => periods_assigned

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

        try {
            DB::beginTransaction();

            // Clear existing timetable for these classes
            TimetableSlot::whereIn('class_room_id', $classIds)
                ->where('academic_term_id', $termId)
                ->delete();

            $classes = ClassRoom::whereIn('id', $classIds)->get();
            $term = AcademicTerm::findOrFail($termId);

            // Step 1: Place combined periods first
            $this->placeCombinedPeriods($classes, $term);

            // Step 2: Generate for each class
            foreach ($classes as $class) {
                $this->generateForClass($class, $term, $options);
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
     * Generate timetable for a single class
     */
    private function generateForClass(ClassRoom $class, AcademicTerm $term, array $options): void
    {
        // Get applicable subjects for this class level
        $subjects = Subject::active()
            ->forLevel($class->level)
            ->orderBy('weekly_periods', 'desc')
            ->get();

        // Get existing slots (combined periods)
        $existingSlots = TimetableSlot::where('class_room_id', $class->id)
            ->where('academic_term_id', $term->id)
            ->get()
            ->groupBy('day')
            ->map(fn($slots) => $slots->pluck('period')->toArray())
            ->toArray();

        // Initialize subject distribution tracker
        $this->subjectDistribution[$class->id] = [];

        // Calculate total periods needed
        $totalPeriods = $class->weekly_periods;
        $days = 6; // Sunday to Friday (0-5)
        $periodsPerDay = 8;

        // Distribute subjects
        foreach ($subjects as $subject) {
            $periodsNeeded = $subject->weekly_periods ?? 4;
            $periodsAssigned = 0;

            // Try to assign periods throughout the week
            for ($day = 0; $day < $days && $periodsAssigned < $periodsNeeded; $day++) {
                for ($period = 1; $period <= $periodsPerDay && $periodsAssigned < $periodsNeeded; $period++) {
                    // Check if slot is already taken
                    if (isset($existingSlots[$day]) && in_array($period, $existingSlots[$day])) {
                        continue;
                    }

                    // Find available teacher
                    $teacher = $this->findAvailableTeacher($subject, $day, $period, $term->id);

                    if (!$teacher) {
                        $this->warnings[] = "No available teacher for {$subject->name} in {$class->full_name} on day $day period $period";
                        continue;
                    }

                    // Create slot
                    TimetableSlot::create([
                        'class_room_id' => $class->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'day' => $day,
                        'period' => $period,
                        'is_combined' => false,
                        'academic_term_id' => $term->id,
                    ]);

                    // Mark slot as taken
                    $existingSlots[$day][] = $period;

                    // Track assignment
                    $this->trackTeacherAssignment($teacher->id, $day);
                    $periodsAssigned++;
                }
            }

            $this->subjectDistribution[$class->id][$subject->id] = $periodsAssigned;

            if ($periodsAssigned < $periodsNeeded) {
                $this->warnings[] = "Only assigned {$periodsAssigned}/{$periodsNeeded} periods for {$subject->name} in {$class->full_name}";
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
            $dailyPeriods = $this->teacherSchedule[$teacher->id][$day] ?? 0;
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
    private function trackTeacherAssignment(int $teacherId, int $day): void
    {
        if (!isset($this->teacherSchedule[$teacherId])) {
            $this->teacherSchedule[$teacherId] = [];
        }

        if (!isset($this->teacherSchedule[$teacherId][$day])) {
            $this->teacherSchedule[$teacherId][$day] = 0;
        }

        $this->teacherSchedule[$teacherId][$day]++;
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