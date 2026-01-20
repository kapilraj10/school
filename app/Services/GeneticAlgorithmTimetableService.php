<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
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

    public function __construct()
    {
        require_once storage_path('Algorithm/GeneticAlgorithm.php');
    }

    public function generateTimetable(
        ClassRoom $classRoom,
        AcademicTerm $academicTerm,
        int $populationSize = 50,
        int $maxGenerations = 500
    ): array {
        try {
            set_time_limit(600);
            ini_set('max_execution_time', '600');

            DB::beginTransaction();

            $this->errors = [];
            $this->warnings = [];

            $this->loadClassData($classRoom);

            if (empty($this->subjects)) {
                throw new \Exception('No subjects configured for this class');
            }

            if (empty($this->teachers)) {
                throw new \Exception('No teachers available for the subjects');
            }

            $scheduler = new \GeneticAlgorithmScheduler(
                $this->subjects,
                $this->teachers,
                $this->sections,
                $populationSize,
                $maxGenerations
            );

            Log::info("Starting genetic algorithm for class: {$classRoom->full_name}");
            $bestTimetable = $scheduler->generateTimetable();

            $slotsCreated = $this->saveTimetableToDatabase($bestTimetable, $classRoom, $academicTerm);

            DB::commit();

            return [
                'success' => true,
                'message' => "Timetable generated successfully for {$classRoom->full_name}",
                'fitness' => $bestTimetable->fitness,
                'slots' => $slotsCreated,
                'warnings' => $this->warnings,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Timetable generation failed', [
                'class' => $classRoom->full_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate timetable: '.$e->getMessage(),
                'errors' => $this->errors,
            ];
        }
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
                $setting->min_periods_per_week ?? $subject->min_periods_per_week ?? 2,
                $setting->max_periods_per_week ?? $subject->max_periods_per_week ?? 6,
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

            $this->loadTeachersForSubject($subject);
        }

        $this->sections[] = $section;
    }

    private function loadTeachersForSubject(Subject $subject): void
    {
        $teachers = Teacher::active()
            ->whereJsonContains('subject_ids', $subject->id)
            ->get();

        foreach ($teachers as $teacher) {
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
                $teacher->max_periods_per_day ?? 7
            );

            $this->teachers[$teacherId] = $gaTeacher;
        }
    }

    private function saveTimetableToDatabase(
        \Timetable $timetable,
        ClassRoom $classRoom,
        AcademicTerm $academicTerm
    ): int {
        TimetableSlot::where('class_room_id', $classRoom->id)
            ->where('academic_term_id', $academicTerm->id)
            ->delete();

        $slotsCreated = 0;
        $sectionId = (string) $classRoom->id;

        if (! isset($timetable->slots[$sectionId])) {
            throw new \Exception("Timetable not generated for section {$sectionId}");
        }

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($timetable->slots[$sectionId] as $dayIndex => $daySlots) {
            foreach ($daySlots as $periodIndex => $timeSlot) {
                if ($timeSlot->subjectId === null || $timeSlot->teacherId === null) {
                    continue;
                }

                $combinedPeriodId = $this->getCombinedPeriodId(
                    $classRoom->id,
                    (int) $timeSlot->subjectId,
                    (int) $timeSlot->teacherId,
                    $dayIndex,
                    $periodIndex,
                    $academicTerm
                );

                TimetableSlot::create([
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
                ]);

                $slotsCreated++;
            }
        }

        return $slotsCreated;
    }

    private function getCombinedPeriodId(
        int $classRoomId,
        int $subjectId,
        int $teacherId,
        int $day,
        int $period,
        AcademicTerm $academicTerm
    ): ?int {
        $subject = Subject::find($subjectId);
        if (! $subject || $subject->single_combined !== 'combined') {
            return null;
        }

        $combinedPeriod = CombinedPeriod::where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->where('day', $day)
            ->where('period', $period + 1)
            ->where('academic_term_id', $academicTerm->id)
            ->first();

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

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
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
