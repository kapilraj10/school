<?php

namespace App\Services;

use App\Models\ClassRange;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSetting;
use Illuminate\Support\Collection;

class TeacherRequirementAnalyzer
{
    private array $schoolDays;

    private int $periodsPerDay;

    private int $maxSameSubjectPerDay;

    public function __construct()
    {
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $schoolDays = TimetableSetting::get('school_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        $this->schoolDays = is_array($schoolDays) ? $schoolDays : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $this->periodsPerDay = (int) TimetableSetting::get('periods_per_day', 8);
        $this->maxSameSubjectPerDay = (int) TimetableSetting::get('max_same_subject_per_day', 2);
    }

    /**
     * Analyze teacher requirements for all classes and subjects
     */
    public function analyzeAll(): Collection
    {
        $results = collect();

        $settings = ClassSubjectSetting::with(['classRoom', 'subject'])
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id');

        foreach ($settings as $subjectId => $subjectSettings) {
            $subject = $subjectSettings->first()->subject;

            // Group by class (combine sections)
            $byClass = $subjectSettings->groupBy(function ($item) {
                return $this->extractClassNumber($item->classRoom->name);
            });

            foreach ($byClass as $classNumber => $classSettings) {
                $requirement = $this->calculateRequirement($subject, $classSettings, $classNumber);
                $results->push($requirement);
            }
        }

        return $results->sortBy(['class_number', 'subject_name'])->values();
    }

    /**
     * Analyze requirements for a specific class
     */
    public function analyzeForClass(int $classRoomId): Collection
    {
        $results = collect();

        $settings = ClassSubjectSetting::with(['classRoom', 'subject'])
            ->where('class_room_id', $classRoomId)
            ->where('is_active', true)
            ->get();

        $classRoom = ClassRoom::find($classRoomId);
        $classNumber = $this->extractClassNumber($classRoom->name);

        foreach ($settings as $setting) {
            $requirement = $this->calculateRequirementSingle($setting, $classNumber);
            $results->push($requirement);
        }

        return $results->sortBy('subject_name')->values();
    }

    /**
     * Analyze requirements grouped by subject (for teachers teaching multiple classes)
     */
    public function analyzeBySubject(): Collection
    {
        $results = collect();

        $subjects = Subject::active()->get();

        foreach ($subjects as $subject) {
            $settings = ClassSubjectSetting::with('classRoom')
                ->where('subject_id', $subject->id)
                ->where('is_active', true)
                ->get();

            if ($settings->isEmpty()) {
                continue;
            }

            $requirement = $this->calculateSubjectRequirement($subject, $settings);
            $results->push($requirement);
        }

        return $results->sortBy('subject_name')->values();
    }

    /**
     * Calculate requirement for a subject across multiple sections of same class
     */
    private function calculateRequirement(Subject $subject, Collection $classSettings, int $classNumber): array
    {
        // Sum up periods needed across all sections
        $totalMinPeriods = $classSettings->sum('min_periods_per_week');
        $totalMaxPeriods = $classSettings->sum('max_periods_per_week');
        $totalWeeklyPeriods = $classSettings->sum('weekly_periods');
        $sectionCount = $classSettings->count();

        // Calculate minimum days needed
        $minDaysNeeded = $this->calculateMinDaysNeeded($totalMinPeriods);
        $maxDaysNeeded = $this->calculateMinDaysNeeded($totalMaxPeriods);

        // Calculate periods per day needed
        $avgPeriodsPerDay = ceil($totalMinPeriods / count($this->schoolDays));
        $maxPeriodsPerDay = ceil($totalMaxPeriods / $minDaysNeeded);

        // Get assigned teacher
        $teacher = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->first();

        // Determine which periods are needed
        $periodsNeeded = $this->calculatePeriodsNeeded($totalMinPeriods, $sectionCount);

        // Get class range
        $classRange = ClassRange::getForClassNumber($classNumber);

        return [
            'class_number' => $classNumber,
            'class_name' => "Class {$classNumber}",
            'class_range' => $classRange?->display_name ?? "Class {$classNumber}",
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'subject_type' => $subject->type ?? 'core',
            'sections' => $classSettings->pluck('classRoom.section')->toArray(),
            'section_count' => $sectionCount,
            'min_periods_per_week' => $totalMinPeriods,
            'max_periods_per_week' => $totalMaxPeriods,
            'weekly_periods' => $totalWeeklyPeriods,
            'min_days_needed' => $minDaysNeeded,
            'max_days_needed' => $maxDaysNeeded,
            'days_recommended' => $this->getRecommendedDays($minDaysNeeded),
            'periods_needed' => $periodsNeeded,
            'periods_per_day_avg' => $avgPeriodsPerDay,
            'periods_per_day_max' => min($maxPeriodsPerDay, $this->periodsPerDay),
            'teacher' => $teacher?->name ?? 'Not Assigned',
            'teacher_id' => $teacher?->id,
            'single_combined' => $classSettings->first()->single_combined,
            'rule_text' => $this->generateRuleText($classNumber, $subject->name, $minDaysNeeded, $periodsNeeded, $teacher?->name),
        ];
    }

    /**
     * Calculate requirement for a single class-subject setting
     */
    private function calculateRequirementSingle(ClassSubjectSetting $setting, int $classNumber): array
    {
        $subject = $setting->subject;
        $classRoom = $setting->classRoom;

        $minDaysNeeded = $this->calculateMinDaysNeeded($setting->min_periods_per_week);
        $periodsNeeded = $this->calculatePeriodsNeeded($setting->min_periods_per_week, 1);

        $teacher = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->first();

        return [
            'class_number' => $classNumber,
            'class_name' => $classRoom->full_name,
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'subject_type' => $subject->type ?? 'core',
            'min_periods_per_week' => $setting->min_periods_per_week,
            'max_periods_per_week' => $setting->max_periods_per_week,
            'weekly_periods' => $setting->weekly_periods,
            'min_days_needed' => $minDaysNeeded,
            'days_recommended' => $this->getRecommendedDays($minDaysNeeded),
            'periods_needed' => $periodsNeeded,
            'teacher' => $teacher?->name ?? 'Not Assigned',
            'teacher_id' => $teacher?->id,
            'single_combined' => $setting->single_combined,
            'rule_text' => $this->generateRuleText($classNumber, $subject->name, $minDaysNeeded, $periodsNeeded, $teacher?->name, $classRoom->section),
        ];
    }

    /**
     * Calculate requirement for a subject across all classes
     */
    private function calculateSubjectRequirement(Subject $subject, Collection $settings): array
    {
        $totalMinPeriods = $settings->sum('min_periods_per_week');
        $totalMaxPeriods = $settings->sum('max_periods_per_week');

        $classNames = $settings->map(fn ($s) => $s->classRoom->full_name)->unique()->values()->toArray();

        // A subject teacher needs to be available across many classes
        // So they likely need to be available all days
        $minDaysNeeded = min(count($this->schoolDays), $this->calculateMinDaysNeeded($totalMinPeriods));

        $teacher = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->first();

        // For a teacher teaching this subject across classes, they need almost all periods
        $periodsPerDay = ceil($totalMinPeriods / count($this->schoolDays));

        return [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'class_room' => $subject->classRoom?->full_name ?? 'N/A',
            'classes' => $classNames,
            'class_count' => count($classNames),
            'total_min_periods' => $totalMinPeriods,
            'total_max_periods' => $totalMaxPeriods,
            'min_days_needed' => $minDaysNeeded,
            'days_recommended' => $this->getRecommendedDays($minDaysNeeded),
            'periods_per_day_needed' => $periodsPerDay,
            'periods_recommended' => range(1, min($periodsPerDay + 2, $this->periodsPerDay)),
            'teacher' => $teacher?->name ?? 'Not Assigned',
            'teacher_id' => $teacher?->id,
            'single_combined' => $subject->single_combined,
            'rule_text' => $this->generateSubjectRuleText($subject->name, $minDaysNeeded, $periodsPerDay, $teacher?->name, count($classNames)),
        ];
    }

    /**
     * Calculate minimum days needed based on periods
     */
    private function calculateMinDaysNeeded(int $periodsNeeded): int
    {
        if ($periodsNeeded <= 0) {
            return 0;
        }

        // With max same subject per day constraint
        $daysNeeded = ceil($periodsNeeded / $this->maxSameSubjectPerDay);

        return min($daysNeeded, count($this->schoolDays));
    }

    /**
     * Calculate which periods are needed
     */
    private function calculatePeriodsNeeded(int $totalPeriods, int $sectionCount): array
    {
        // If teacher has to teach multiple sections, they need more period availability
        $periodsPerDayNeeded = ceil($totalPeriods / count($this->schoolDays));

        // Add buffer for flexibility
        $periodsPerDayNeeded = min($periodsPerDayNeeded + 1, $this->periodsPerDay);

        return range(1, $periodsPerDayNeeded);
    }

    /**
     * Get recommended days based on minimum needed
     */
    private function getRecommendedDays(int $minDays): array
    {
        return array_slice($this->schoolDays, 0, max($minDays, 1));
    }

    /**
     * Generate human-readable rule text
     */
    private function generateRuleText(int $classNumber, string $subjectName, int $minDays, array $periodsNeeded, ?string $teacherName, ?string $section = null): string
    {
        $classLabel = $section ? "class {$classNumber}{$section}" : "class {$classNumber}";
        $teacherLabel = 'teacher';

        $daysText = $minDays >= count($this->schoolDays)
            ? 'all school days'
            : strtolower(implode(', ', array_slice($this->schoolDays, 0, $minDays)));

        $periodsText = count($periodsNeeded) === 1
            ? 'period '.implode(', ', $periodsNeeded)
            : 'period '.implode(' and ', $periodsNeeded);

        return "{$teacherLabel} for ".strtolower($subjectName)." in {$classLabel} should be available on {$daysText} during {$periodsText}";
    }

    /**
     * Generate rule text for subject-level analysis
     */
    private function generateSubjectRuleText(string $subjectName, int $minDays, int $periodsPerDay, ?string $teacherName, int $classCount): string
    {
        $teacherLabel = $teacherName ?? 'The teacher';

        $daysText = $minDays >= count($this->schoolDays)
            ? 'all school days'
            : implode(', ', array_slice($this->schoolDays, 0, $minDays));

        $periodsText = $periodsPerDay >= $this->periodsPerDay
            ? 'all periods (1-'.$this->periodsPerDay.')'
            : 'at least periods 1-'.$periodsPerDay;

        return "{$teacherLabel} for {$subjectName} (teaching {$classCount} classes) should be available on {$daysText} during {$periodsText}.";
    }

    /**
     * Extract class number from class name
     */
    private function extractClassNumber(string $className): int
    {
        if (preg_match('/(\d+)/', $className, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * Get settings info
     */
    public function getSettings(): array
    {
        return [
            'school_days' => $this->schoolDays,
            'periods_per_day' => $this->periodsPerDay,
            'max_same_subject_per_day' => $this->maxSameSubjectPerDay,
        ];
    }
}
