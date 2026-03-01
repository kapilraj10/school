<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher as TeacherModel;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneticAlgorithmTimetableService
{
    private array $subjects = [];

    private array $teachers = [];

    private array $sections = [];

    private array $errors = [];

    private array $warnings = [];

    private bool $clearExisting = true;

    public function __construct()
    {
        require_once storage_path('Algorithm/GeneticAlgorithm.php');
    }

    public function setClearExisting(bool $clearExisting): self
    {
        $this->clearExisting = $clearExisting;

        return $this;
    }

    public function generateTimetable(
        ClassRoom $classRoom,
        AcademicTerm $academicTerm,
        int $populationSize = 25,
        int $maxGenerations = 100
    ): array {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $this->errors = [];
        $this->warnings = [];
        $this->subjects = [];
        $this->teachers = [];
        $this->sections = [];

        // Phase 1: Load data from DB (read-only, fast)
        try {
            $this->loadClassData($classRoom);

            if (empty($this->subjects)) {
                throw new \Exception('No subjects configured for this class');
            }

            if (empty($this->teachers)) {
                throw new \Exception('No teachers available for the subjects');
            }

            $lockedSlots = $this->loadLockedSlots($classRoom, $academicTerm);
        } catch (\Throwable $e) {
            Log::error('Timetable data load failed', [
                'class' => $classRoom->full_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to load class data: '.$e->getMessage(),
                'errors' => $this->errors,
            ];
        }

        // Phase 2: Run the genetic algorithm (no DB writes)
        try {
            \SchedulerConfig::init();

            $scheduler = new \GeneticAlgorithmScheduler(
                $this->subjects,
                $this->teachers,
                $this->sections,
                $populationSize,
                $maxGenerations,
                $lockedSlots
            );

            $generationStartedAt = microtime(true);

            Log::info("Starting genetic algorithm for class: {$classRoom->full_name}");
            $bestTimetable = $scheduler->generateTimetable();
            $generationDurationSeconds = round(microtime(true) - $generationStartedAt, 3);

            $finalRepair = new \TimetableRepair($this->subjects, $this->teachers, $this->sections);
            $finalRepair->repair($bestTimetable);

            Log::info('Genetic algorithm completed', [
                'class' => $classRoom->full_name,
                'duration_seconds' => $generationDurationSeconds,
                'population_size' => $populationSize,
                'max_generations' => $maxGenerations,
                'subjects_count' => count($this->subjects),
                'teachers_count' => count($this->teachers),
                'fitness' => $bestTimetable->fitness,
                'clear_existing' => $this->clearExisting,
            ]);
        } catch (\Throwable $e) {
            Log::error('Genetic algorithm failed', [
                'class' => $classRoom->full_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'GA failed: '.$e->getMessage(),
                'errors' => $this->errors,
            ];
        }

        // Phase 3: Save to DB with retry on SQLite lock
        $maxAttempts = 6;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $slotsCreated = DB::transaction(function () use ($bestTimetable, $classRoom, $academicTerm) {
                    return $this->saveTimetableToDatabase($bestTimetable, $classRoom, $academicTerm, $this->clearExisting);
                });

                return [
                    'success' => true,
                    'message' => "Timetable generated successfully for {$classRoom->full_name}",
                    'fitness' => $bestTimetable->fitness,
                    'slots' => $slotsCreated,
                    'warnings' => $this->warnings,
                ];
            } catch (\Throwable $e) {
                $isLocked = str_contains($e->getMessage(), 'database is locked')
                    || str_contains($e->getMessage(), 'General error: 5');

                if ($isLocked && $attempt < $maxAttempts) {
                    Log::warning("SQLite locked saving {$classRoom->full_name}, retrying (attempt {$attempt}/{$maxAttempts})");
                    usleep(300000 * $attempt); // 300ms, 600ms, 900ms, 1200ms, 1500ms

                    continue;
                }

                Log::error('Timetable save failed', [
                    'class' => $classRoom->full_name,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to save timetable: '.$e->getMessage(),
                    'errors' => $this->errors,
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Failed to save timetable after '.$maxAttempts.' attempts (database locked)',
            'errors' => $this->errors,
        ];
    }

    private function loadLockedSlots(ClassRoom $classRoom, AcademicTerm $academicTerm): array
    {
        $lockedSlots = [];
        $slots = TimetableSlot::where('class_room_id', $classRoom->id)
            ->where('academic_term_id', $academicTerm->id)
            ->where('is_locked', true)
            ->get();

        foreach ($slots as $slot) {
            $sectionId = (string) $classRoom->id;
            if (! isset($lockedSlots[$sectionId])) {
                $lockedSlots[$sectionId] = [];
            }
            if (! isset($lockedSlots[$sectionId][$slot->day])) {
                $lockedSlots[$sectionId][$slot->day] = [];
            }
            $lockedSlots[$sectionId][$slot->day][$slot->period - 1] = [
                'subject_id' => (string) $slot->subject_id,
                'teacher_id' => (string) $slot->teacher_id,
            ];
        }

        return $lockedSlots;
    }

    private function loadClassData(ClassRoom $classRoom): void
    {
        $classSubjectSettings = ClassSubjectSetting::with('subject')
            ->where('class_room_id', $classRoom->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        if ($classSubjectSettings->isEmpty()) {
            throw new \Exception('No active subjects configured for this class. Please configure subjects first.');
        }

        $section = new \Section(
            (string) $classRoom->id,
            $this->extractGradeLevel($classRoom->name),
            $classRoom->section
        );

        foreach ($classSubjectSettings as $setting) {
            $subject = $setting->subject;

            if (! $subject || $subject->status !== 'active') {
                continue;
            }

            $gaSubject = new \Subject(
                (string) $subject->id,
                $subject->name,
                $this->mapSubjectType($subject->type),
                $setting->min_periods_per_week ?? 2,
                $setting->max_periods_per_week ?? 6,
                $setting->single_combined === 'combined'
            );

            $this->subjects[(string) $subject->id] = $gaSubject;

            $subjectType = $gaSubject->type;
            if ($subjectType === \SchedulerConfig::TYPE_COMPULSORY) {
                $section->compulsorySubjects[] = (string) $subject->id;
            } elseif ($subjectType === \SchedulerConfig::TYPE_OPTIONAL) {
                $section->optionalSubjects[] = (string) $subject->id;
            } elseif ($subjectType === \SchedulerConfig::TYPE_CO_CURRICULAR) {
                $section->coCurricularSubjects[] = (string) $subject->id;
            }

            $this->loadTeachersForSubject($subject, $classRoom);
        }

        $this->sections[] = $section;
    }

    private function loadTeachersForSubject(Subject $subject, ClassRoom $classRoom): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, TeacherModel> $teachers */
        $teachers = TeacherModel::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->get();

        /** @var TeacherModel $teacher */
        foreach ($teachers as $teacher) {
            if (! $teacher->canTeachClass($classRoom->id)) {
                continue;
            }

            $teacherId = (string) $teacher->id;

            if (isset($this->teachers[$teacherId])) {
                if (! in_array((string) $subject->id, $this->teachers[$teacherId]->subjects)) {
                    $this->teachers[$teacherId]->subjects[] = (string) $subject->id;
                }

                continue;
            }

            $gaTeacher = new \Teacher(
                $teacherId,
                $teacher->name,
                array_map('strval', $teacher->subject_ids ?? []),
                $teacher->max_periods_per_day ?? 7,
                $teacher->availability_matrix
            );

            $this->teachers[$teacherId] = $gaTeacher;
        }
    }

    private function saveTimetableToDatabase(
        \Timetable $timetable,
        ClassRoom $classRoom,
        AcademicTerm $academicTerm,
        bool $clearExisting = true
    ): int {
        $existingSlots = TimetableSlot::where('class_room_id', $classRoom->id)
            ->where('academic_term_id', $academicTerm->id)
            ->get();

        $lockedSlots = $existingSlots
            ->where('is_locked', true)
            ->keyBy(fn ($slot) => "{$slot->day}_{$slot->period}");

        $existingUnlockedSlots = $existingSlots
            ->where('is_locked', false)
            ->keyBy(fn ($slot) => "{$slot->day}_{$slot->period}");

        if ($clearExisting) {
            TimetableSlot::where('class_room_id', $classRoom->id)
                ->where('academic_term_id', $academicTerm->id)
                ->where('is_locked', false)
                ->delete();

            $existingUnlockedSlots = collect();
        }

        $slotsToInsert = [];
        $sectionId = (string) $classRoom->id;

        if (! isset($timetable->slots[$sectionId])) {
            throw new \Exception("Timetable not generated for section {$sectionId}");
        }

        $days = TimetableSlot::getDays();
        $timestamp = now();

        $combinedPeriodCache = $this->loadCombinedPeriodsCache($academicTerm);

        foreach ($timetable->slots[$sectionId] as $dayIndex => $daySlots) {
            foreach ($daySlots as $periodIndex => $timeSlot) {
                if ($timeSlot->subjectId === null || $timeSlot->teacherId === null) {
                    continue;
                }

                // Skip positions where locked slots exist (preserve locked entries)
                $slotKey = "{$dayIndex}_".($periodIndex + 1);
                if ($lockedSlots->has($slotKey)) {
                    continue;
                }

                // When preserving existing timetable, don't overwrite existing unlocked slots
                if (! $clearExisting && $existingUnlockedSlots->has($slotKey)) {
                    continue;
                }

                $combinedPeriodId = $this->getCombinedPeriodIdFromCache(
                    $classRoom->id,
                    (int) $timeSlot->subjectId,
                    (int) $timeSlot->teacherId,
                    $dayIndex,
                    $periodIndex,
                    $academicTerm,
                    $combinedPeriodCache
                );

                $slotsToInsert[] = [
                    'class_room_id' => $classRoom->id,
                    'subject_id' => (int) $timeSlot->subjectId,
                    'teacher_id' => (int) $timeSlot->teacherId,
                    'academic_term_id' => $academicTerm->id,
                    'combined_period_id' => $combinedPeriodId,
                    'day' => $dayIndex,
                    'period' => $periodIndex + 1,
                    'type' => 'regular',
                    'status' => 'published',
                    'is_combined' => $combinedPeriodId !== null,
                    'is_locked' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if (! empty($slotsToInsert)) {
            TimetableSlot::insert($slotsToInsert);
        }

        return count($slotsToInsert) + $lockedSlots->count() + $existingUnlockedSlots->count();
    }

    private function loadCombinedPeriodsCache(AcademicTerm $academicTerm): array
    {
        $combinedPeriods = CombinedPeriod::where('academic_term_id', $academicTerm->id)
            ->get();

        $cache = [];
        foreach ($combinedPeriods as $period) {
            $key = "{$period->subject_id}_{$period->teacher_id}_{$period->day}_{$period->period}";
            $cache[$key] = $period;
        }

        return $cache;
    }

    private function getCombinedPeriodIdFromCache(
        int $classRoomId,
        int $subjectId,
        int $teacherId,
        int $day,
        int $period,
        AcademicTerm $academicTerm,
        array $combinedPeriodCache
    ): ?int {
        // Check if subject is combined via ClassSubjectSetting
        static $settingCache = [];
        $cacheKey = "{$classRoomId}_{$subjectId}";
        if (! isset($settingCache[$cacheKey])) {
            $settingCache[$cacheKey] = ClassSubjectSetting::where('class_room_id', $classRoomId)
                ->where('subject_id', $subjectId)
                ->first();
        }

        $classSetting = $settingCache[$cacheKey];
        if (! $classSetting || ($classSetting->single_combined ?? 'single') !== 'combined') {
            return null;
        }

        $subject = $classSetting->subject;
        if (! $subject) {
            return null;
        }

        // Look up in cache
        $key = "{$subjectId}_{$teacherId}_{$day}_".($period + 1);
        $combinedPeriod = $combinedPeriodCache[$key] ?? null;

        if ($combinedPeriod) {
            if (! in_array($classRoomId, $combinedPeriod->class_room_ids ?? [])) {
                $combinedPeriod->class_room_ids = array_merge(
                    $combinedPeriod->class_room_ids ?? [],
                    [$classRoomId]
                );
                $combinedPeriod->save();
            }

            return $combinedPeriod->id;
        }

        $days = TimetableSlot::getDays();
        $dayName = $days[$day] ?? 'Unknown';
        $periodNum = $period + 1;

        $newCombinedPeriod = CombinedPeriod::create([
            'name' => "{$subject->name} - {$dayName} P{$periodNum}",
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'day' => $day,
            'period' => $periodNum,
            'academic_term_id' => $academicTerm->id,
            'class_room_ids' => [$classRoomId],
            'frequency' => 'weekly',
        ]);

        return $newCombinedPeriod->id;
    }

    private function extractGradeLevel(string $className): string
    {
        if (preg_match('/(\d+)/', $className, $matches)) {
            return $matches[1];
        }

        return '1';
    }

    private function mapSubjectType(string $type): string
    {
        return match ($type) {
            'core', 'compulsory' => \SchedulerConfig::TYPE_COMPULSORY,
            'elective', 'optional' => \SchedulerConfig::TYPE_OPTIONAL,
            'co_curricular', 'co-curricular' => \SchedulerConfig::TYPE_CO_CURRICULAR,
            default => \SchedulerConfig::TYPE_COMPULSORY,
        };
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
