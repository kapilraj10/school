<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Conflict;
use App\Models\Subject;
use App\Models\TimetableSlot;
use App\Services\ConflictResolverService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConflictChecker extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.conflict-checker';

    protected static ?string $navigationLabel = 'Conflict Checker';

    protected static ?string $title = 'Conflict Checker';

    protected static ?string $navigationGroup = 'Timetable Management';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public $conflicts = null;

    /**
     * Physical / co-curricular subject codes that should be placed in period 5.
     */
    protected array $physicalSubjectCodes = ['sports', 'sport', 'taekwondo', 'dance'];

    /**
     * Mentally heavy subject types/codes used for soft-check on consecutive scheduling.
     */
    protected array $heavySubjectCodes = ['english', 'math', 'maths', 'mathematics', 'science'];

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();

        $this->form->fill([
            'academic_term_id' => $currentTerm?->id,
        ]);

        if ($currentTerm) {
            $this->checkConflicts();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('academic_term_id')
                    ->label('Academic Term')
                    ->options(AcademicTerm::query()->orderBy('year', 'desc')->orderBy('term', 'desc')->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->checkConflicts())
                    ->native(false)
                    ->searchable(),
            ])
            ->statePath('data');
    }

    // ─── Main entry ───────────────────────────────────────────────

    public function checkConflicts(): void
    {
        $data = $this->form->getState();

        if (! isset($data['academic_term_id'])) {
            $this->conflicts = null;

            return;
        }

        $termId = $data['academic_term_id'];

        // Pre-load all slots for the term once
        $allSlots = TimetableSlot::where('academic_term_id', $termId)
            ->with(['subject', 'teacher', 'classRoom'])
            ->get();

        // Truncate old conflicts
        Conflict::truncateForTerm($termId);

        // ─── Hard constraints ──────────────────────────────────
        $this->checkTeacherDoubleBooking($termId);
        $this->checkClassroomDoubleBooking($termId);
        $this->checkTeacherUnavailability($termId, $allSlots);
        $this->checkWeeklyOverload($termId);
        $this->checkDailyOverloadHard($termId);
        $this->checkMinMaxPeriods($termId);
        $this->checkCombinedPeriodAdjacencyViolations($termId);
        $this->checkEmptySlots($termId, $allSlots);
        $this->checkTotalPeriodsPerWeek($termId, $allSlots);
        $this->checkCoCurricularSameDay($termId, $allSlots);
        $this->checkCoCurricularConsecutive($termId, $allSlots);
        $this->checkSubjectDailyExcess($termId, $allSlots);
        $this->checkCombinedGradeSameDay($termId, $allSlots);
        $this->checkPhysicalPeriodPlacement($termId, $allSlots);

        // ─── Soft constraints ──────────────────────────────────
        $this->checkPositionalConsistency($termId, $allSlots);
        $this->checkCoreSubjectConsistency($termId, $allSlots);
        $this->checkConsecutiveHeavySubjects($termId, $allSlots);
        $this->checkCoCurricularPlacement($termId, $allSlots);
        $this->checkSubjectDailyBalance($termId, $allSlots);

        // Reload grouped conflicts
        $this->conflicts = Conflict::getGroupedByType($termId);
        $this->conflicts['term'] = AcademicTerm::find($termId);
    }

    // ─── HARD CONSTRAINTS ─────────────────────────────────────────

    /**
     * H1 – Teacher cannot teach two classes in the same period.
     */
    protected function checkTeacherDoubleBooking(int $termId): void
    {
        $teacherConflicts = DB::table('timetable_slots as t1')
            ->join('timetable_slots as t2', function ($join) use ($termId) {
                $join->on('t1.teacher_id', '=', 't2.teacher_id')
                    ->on('t1.day', '=', 't2.day')
                    ->on('t1.period', '=', 't2.period')
                    ->where('t1.academic_term_id', $termId)
                    ->where('t2.academic_term_id', $termId)
                    ->whereColumn('t1.id', '<', 't2.id');
            })
            ->join('teachers', 't1.teacher_id', '=', 'teachers.id')
            ->join('class_rooms as c1', 't1.class_room_id', '=', 'c1.id')
            ->join('class_rooms as c2', 't2.class_room_id', '=', 'c2.id')
            ->join('subjects as s1', 't1.subject_id', '=', 's1.id')
            ->join('subjects as s2', 't2.subject_id', '=', 's2.id')
            ->select(
                'teachers.name as teacher_name',
                'teachers.id as teacher_id',
                't1.day',
                't1.period',
                's1.name as subject1',
                's2.name as subject2',
                DB::raw("c1.name || ' - ' || c1.section as class1"),
                DB::raw("c2.name || ' - ' || c2.section as class2")
            )
            ->get();

        foreach ($teacherConflicts as $conflict) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'teacher_conflict',
                'severity' => 'critical',
                'entity_type' => 'teacher',
                'entity_id' => $conflict->teacher_id,
                'data' => (array) $conflict,
            ]);
        }
    }

    /**
     * H2 – Same class cannot have two different subjects in the same period.
     */
    protected function checkClassroomDoubleBooking(int $termId): void
    {
        $classroomConflicts = DB::table('timetable_slots as t1')
            ->join('timetable_slots as t2', function ($join) use ($termId) {
                $join->on('t1.day', '=', 't2.day')
                    ->on('t1.period', '=', 't2.period')
                    ->where('t1.academic_term_id', $termId)
                    ->where('t2.academic_term_id', $termId)
                    ->whereColumn('t1.id', '<', 't2.id')
                    ->whereColumn('t1.class_room_id', '=', 't2.class_room_id');
            })
            ->join('class_rooms as cr', 't1.class_room_id', '=', 'cr.id')
            ->join('subjects as s1', 't1.subject_id', '=', 's1.id')
            ->join('subjects as s2', 't2.subject_id', '=', 's2.id')
            ->join('teachers as teacher1', 't1.teacher_id', '=', 'teacher1.id')
            ->join('teachers as teacher2', 't2.teacher_id', '=', 'teacher2.id')
            ->select(
                'cr.id as classroom_id',
                DB::raw("cr.name || COALESCE(' - ' || cr.section, '') as classroom_name"),
                't1.day',
                't1.period',
                's1.name as subject1',
                's2.name as subject2',
                'teacher1.name as teacher1',
                'teacher2.name as teacher2'
            )
            ->get();

        foreach ($classroomConflicts as $conflict) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'classroom_conflict',
                'severity' => 'critical',
                'entity_type' => 'classroom',
                'entity_id' => $conflict->classroom_id,
                'data' => (array) $conflict,
            ]);
        }
    }

    /**
     * H3 – Teacher scheduled when marked unavailable.
     */
    protected function checkTeacherUnavailability(int $termId, Collection $allSlots): void
    {
        // Map integer day indices to the short-name keys used in availability_matrix
        $dayKeyMap = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri'];

        $violations = $allSlots->filter(function ($slot) use ($dayKeyMap) {
            if (! $slot->teacher) {
                return false;
            }
            $matrix = is_string($slot->teacher->availability_matrix)
                ? json_decode($slot->teacher->availability_matrix, true)
                : $slot->teacher->availability_matrix;

            if (empty($matrix)) {
                return false;
            }

            $dayKey = $dayKeyMap[$slot->day] ?? null;
            if ($dayKey === null || ! isset($matrix[$dayKey])) {
                return false;
            }

            return ! ($matrix[$dayKey][$slot->period] ?? false);
        });

        foreach ($violations as $slot) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'unavailable_violation',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => $slot->teacher_id,
                'data' => [
                    'teacher_name' => $slot->teacher->name,
                    'day' => $slot->day,
                    'period' => $slot->period,
                ],
            ]);
        }
    }

    /**
     * H4 – Teacher exceeds max periods per week.
     */
    protected function checkWeeklyOverload(int $termId): void
    {
        $overloaded = DB::table('timetable_slots as ts')
            ->join('teachers as t', 'ts.teacher_id', '=', 't.id')
            ->where('ts.academic_term_id', $termId)
            ->select(
                't.id',
                't.name',
                't.max_periods_per_week',
                DB::raw('COUNT(*) as assigned_periods')
            )
            ->groupBy('t.id', 't.name', 't.max_periods_per_week')
            ->havingRaw('COUNT(*) > t.max_periods_per_week')
            ->get();

        foreach ($overloaded as $teacher) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'overloaded_teacher',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => $teacher->id,
                'data' => (array) $teacher,
            ]);
        }
    }

    /**
     * H5 – Teacher exceeds max periods per day (hard cap 7).
     */
    protected function checkDailyOverloadHard(int $termId): void
    {
        $dailyAssignments = DB::table('timetable_slots as ts')
            ->join('teachers as t', 'ts.teacher_id', '=', 't.id')
            ->where('ts.academic_term_id', $termId)
            ->select(
                't.id as teacher_id',
                't.name as teacher_name',
                't.max_periods_per_day',
                'ts.day',
                DB::raw('COUNT(*) as daily_periods')
            )
            ->groupBy('t.id', 't.name', 't.max_periods_per_day', 'ts.day')
            ->get();

        foreach ($dailyAssignments as $row) {
            $maxAllowed = min($row->max_periods_per_day ?: 7, 7);
            if ($row->daily_periods > $maxAllowed) {
                Conflict::create([
                    'academic_term_id' => $termId,
                    'type' => 'daily_overload',
                    'severity' => 'high',
                    'entity_type' => 'teacher',
                    'entity_id' => $row->teacher_id,
                    'data' => [
                        'teacher_id' => $row->teacher_id,
                        'teacher_name' => $row->teacher_name,
                        'day' => $row->day,
                        'day_name' => TimetableSlot::getDays()[$row->day] ?? "Day {$row->day}",
                        'assigned_periods' => $row->daily_periods,
                        'max_periods' => $maxAllowed,
                        'excess' => $row->daily_periods - $maxAllowed,
                    ],
                ]);
            }
        }
    }

    /**
     * H6 – Min / max weekly period allocation per class-subject.
     */
    protected function checkMinMaxPeriods(int $termId): void
    {
        $slotsByClassSubject = DB::table('timetable_slots as ts')
            ->join('class_rooms as cr', 'ts.class_room_id', '=', 'cr.id')
            ->join('subjects as s', 'ts.subject_id', '=', 's.id')
            ->where('ts.academic_term_id', $termId)
            ->select(
                'ts.class_room_id',
                'ts.subject_id',
                'cr.name as class_name',
                'cr.section as class_section',
                's.name as subject_name',
                DB::raw('COUNT(*) as assigned_periods')
            )
            ->groupBy('ts.class_room_id', 'ts.subject_id', 'cr.name', 'cr.section', 's.name')
            ->get();

        foreach ($slotsByClassSubject as $slot) {
            $setting = ClassSubjectSetting::where('class_room_id', $slot->class_room_id)
                ->where('subject_id', $slot->subject_id)
                ->where('is_active', true)
                ->first();

            if (! $setting) {
                continue;
            }

            $className = $slot->class_name.($slot->class_section ? ' - '.$slot->class_section : '');

            if ($slot->assigned_periods < $setting->min_periods_per_week) {
                Conflict::create([
                    'academic_term_id' => $termId,
                    'type' => 'min_period_violation',
                    'severity' => 'high',
                    'entity_type' => 'class_subject',
                    'entity_id' => null,
                    'data' => [
                        'class_name' => $className,
                        'subject_name' => $slot->subject_name,
                        'assigned' => $slot->assigned_periods,
                        'minimum' => $setting->min_periods_per_week,
                        'deficit' => $setting->min_periods_per_week - $slot->assigned_periods,
                    ],
                ]);
            }

            if ($slot->assigned_periods > $setting->max_periods_per_week) {
                Conflict::create([
                    'academic_term_id' => $termId,
                    'type' => 'max_period_violation',
                    'severity' => 'high',
                    'entity_type' => 'class_subject',
                    'entity_id' => null,
                    'data' => [
                        'class_name' => $className,
                        'subject_name' => $slot->subject_name,
                        'assigned' => $slot->assigned_periods,
                        'maximum' => $setting->max_periods_per_week,
                        'excess' => $slot->assigned_periods - $setting->max_periods_per_week,
                    ],
                ]);
            }
        }
    }

    /**
     * H7 – Combined subjects must have adjacent periods on the same day.
     */
    protected function checkCombinedPeriodAdjacencyViolations(int $termId): void
    {
        $combinedSettings = ClassSubjectSetting::where('single_combined', 'combined')
            ->where('is_active', true)
            ->with(['classRoom', 'subject'])
            ->get();

        foreach ($combinedSettings as $setting) {
            if (! $setting->classRoom || ! $setting->subject) {
                continue;
            }

            $periods = TimetableSlot::where('academic_term_id', $termId)
                ->where('class_room_id', $setting->class_room_id)
                ->where('subject_id', $setting->subject_id)
                ->orderBy('day')
                ->orderBy('period')
                ->get(['day', 'period']);

            $dayPeriods = $periods->groupBy('day');

            foreach ($dayPeriods as $day => $daySlots) {
                if ($daySlots->count() < 2) {
                    continue;
                }

                $periodNumbers = $daySlots->pluck('period')->sort()->values()->all();

                for ($i = 0; $i < count($periodNumbers) - 1; $i++) {
                    if ($periodNumbers[$i + 1] - $periodNumbers[$i] > 1) {
                        $dayName = TimetableSlot::getDays()[$day] ?? "Day $day";
                        Conflict::create([
                            'academic_term_id' => $termId,
                            'type' => 'combined_period_violation',
                            'severity' => 'high',
                            'entity_type' => 'class_subject',
                            'entity_id' => null,
                            'data' => [
                                'class_name' => $setting->classRoom->full_name,
                                'subject_name' => $setting->subject->name,
                                'issue' => 'Combined subject must have adjacent periods',
                                'details' => "Non-adjacent periods on {$dayName}: Period {$periodNumbers[$i]} and Period {$periodNumbers[$i + 1]}",
                            ],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * H8 – All 48 periods must be filled per class per week (no empty slots).
     */
    protected function checkEmptySlots(int $termId, Collection $allSlots): void
    {
        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            // Count filled slots (slots with a subject assigned)
            $filledCount = $classSlots->whereNotNull('subject_id')->count();

            // Check for missing slot positions (should have 6 days × 8 periods = 48)
            $expectedTotal = 48;
            $existingPositions = $classSlots->map(fn ($s) => $s->day.'-'.$s->period)->unique()->count();

            if ($existingPositions < $expectedTotal || $filledCount < $expectedTotal) {
                $emptyCount = $expectedTotal - $filledCount;
                $missingCount = $expectedTotal - $existingPositions;

                Conflict::create([
                    'academic_term_id' => $termId,
                    'type' => 'empty_slot_violation',
                    'severity' => 'critical',
                    'entity_type' => 'classroom',
                    'entity_id' => $class->id,
                    'data' => [
                        'class_name' => $class->full_name,
                        'expected' => $expectedTotal,
                        'filled' => $filledCount,
                        'empty' => $emptyCount,
                        'missing_slots' => $missingCount,
                    ],
                ]);
            }
        }
    }

    /**
     * H9 – Each class must have exactly 8 periods taught per day.
     */
    protected function checkTotalPeriodsPerWeek(int $termId, Collection $allSlots): void
    {
        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            foreach (TimetableSlot::getDays() as $dayNum => $dayName) {
                $daySlots = $classSlots->where('day', $dayNum)->whereNotNull('subject_id');
                if ($daySlots->count() !== 8) {
                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'total_period_violation',
                        'severity' => 'high',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'day' => $dayNum,
                            'day_name' => $dayName,
                            'expected' => 8,
                            'actual' => $daySlots->count(),
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * H10 – No two different co-curricular subjects in a single day for a class.
     */
    protected function checkCoCurricularSameDay(int $termId, Collection $allSlots): void
    {
        $coCurricularSubjectIds = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('id', 'id');

        if ($coCurricularSubjectIds->isEmpty()) {
            return;
        }

        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            foreach (TimetableSlot::getDays() as $dayNum => $dayName) {
                $dayCoCurricular = $classSlots
                    ->where('day', $dayNum)
                    ->whereIn('subject_id', $coCurricularSubjectIds->keys())
                    ->pluck('subject_id')
                    ->unique();

                if ($dayCoCurricular->count() > 1) {
                    $subjectNames = Subject::whereIn('id', $dayCoCurricular)->pluck('name')->implode(', ');
                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'cocurricular_same_day',
                        'severity' => 'critical',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'day' => $dayNum,
                            'day_name' => $dayName,
                            'subjects' => $subjectNames,
                            'count' => $dayCoCurricular->count(),
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * H11 – Co-curricular: max 2 periods/day, must be same subject & consecutive.
     *        e.g. dance-dance OK; dance-sport or dance-math-dance NOT OK.
     */
    protected function checkCoCurricularConsecutive(int $termId, Collection $allSlots): void
    {
        $coCurricularIds = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('id', 'id');

        if ($coCurricularIds->isEmpty()) {
            return;
        }

        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            foreach (TimetableSlot::getDays() as $dayNum => $dayName) {
                $dayCoCurricular = $classSlots
                    ->where('day', $dayNum)
                    ->whereIn('subject_id', $coCurricularIds->keys())
                    ->sortBy('period');

                if ($dayCoCurricular->count() <= 1) {
                    continue;
                }

                // All must be the same subject
                $uniqueSubjects = $dayCoCurricular->pluck('subject_id')->unique();
                if ($uniqueSubjects->count() > 1) {
                    // Already caught by H10; skip duplicate
                    continue;
                }

                // Max 2 periods
                if ($dayCoCurricular->count() > 2) {
                    $subjectName = $dayCoCurricular->first()->subject->name ?? 'Unknown';
                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'cocurricular_consecutive',
                        'severity' => 'critical',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'day_name' => $dayName,
                            'subject' => $subjectName,
                            'issue' => "Co-curricular '{$subjectName}' has {$dayCoCurricular->count()} periods (max 2)",
                        ],
                    ]);

                    continue;
                }

                // Exactly 2 – must be consecutive
                $periods = $dayCoCurricular->pluck('period')->sort()->values();
                if ($periods->count() === 2 && ($periods[1] - $periods[0]) !== 1) {
                    $subjectName = $dayCoCurricular->first()->subject->name ?? 'Unknown';
                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'cocurricular_consecutive',
                        'severity' => 'critical',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'day_name' => $dayName,
                            'subject' => $subjectName,
                            'issue' => "Co-curricular '{$subjectName}' periods {$periods[0]} & {$periods[1]} are not consecutive",
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * H12 – Not more than 2 periods of the same subject in a single day.
     */
    protected function checkSubjectDailyExcess(int $termId, Collection $allSlots): void
    {
        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            foreach (TimetableSlot::getDays() as $dayNum => $dayName) {
                $daySlots = $classSlots->where('day', $dayNum)->whereNotNull('subject_id');

                $subjectCounts = $daySlots->groupBy('subject_id');

                foreach ($subjectCounts as $subjectId => $slots) {
                    if ($slots->count() > 2) {
                        $subjectName = $slots->first()->subject->name ?? 'Unknown';
                        Conflict::create([
                            'academic_term_id' => $termId,
                            'type' => 'subject_daily_excess',
                            'severity' => 'critical',
                            'entity_type' => 'classroom',
                            'entity_id' => $class->id,
                            'data' => [
                                'class_name' => $class->full_name,
                                'day_name' => $dayName,
                                'subject_name' => $subjectName,
                                'count' => $slots->count(),
                                'max_allowed' => 2,
                            ],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * H13 – Combined subjects must be scheduled for the same grade, same day, same periods.
     *        e.g. Sport on Tue P5-6 for Class 1A & 1B is OK.
     *        Sport on Tue P5-6 for Class 1A & Class 2A is NOT OK (different grades).
     */
    protected function checkCombinedGradeSameDay(int $termId, Collection $allSlots): void
    {
        // Get all combined-type slots
        $combinedSlots = $allSlots->where('is_combined', true)->whereNotNull('combined_period_id');

        if ($combinedSlots->isEmpty()) {
            return;
        }

        // Group by combined_period_id
        $byCombinedPeriod = $combinedSlots->groupBy('combined_period_id');

        foreach ($byCombinedPeriod as $combinedPeriodId => $slots) {
            // Extract unique class grade numbers from class names
            $classGrades = $slots->map(function ($slot) {
                if (! $slot->classRoom) {
                    return null;
                }

                return (int) filter_var($slot->classRoom->name, FILTER_SANITIZE_NUMBER_INT);
            })->filter()->unique();

            if ($classGrades->count() > 1) {
                $subjectName = $slots->first()->subject->name ?? 'Unknown';
                $classNames = $slots->map(fn ($s) => $s->classRoom?->full_name)->filter()->unique()->implode(', ');
                $dayName = TimetableSlot::getDays()[$slots->first()->day] ?? 'Unknown';

                Conflict::create([
                    'academic_term_id' => $termId,
                    'type' => 'combined_grade_violation',
                    'severity' => 'critical',
                    'entity_type' => 'class_subject',
                    'entity_id' => null,
                    'data' => [
                        'subject_name' => $subjectName,
                        'classes' => $classNames,
                        'grades' => $classGrades->values()->implode(', '),
                        'day_name' => $dayName,
                        'issue' => "Combined subject '{$subjectName}' spans different grades ({$classGrades->values()->implode(', ')}). It must be within the same grade.",
                    ],
                ]);
            }

            // Also check that all sections of the combined period share the same day
            $uniqueDays = $slots->pluck('day')->unique();

            if ($uniqueDays->count() > 1) {
                $subjectName = $slots->first()->subject->name ?? 'Unknown';
                Conflict::create([
                    'academic_term_id' => $termId,
                    'type' => 'combined_grade_violation',
                    'severity' => 'critical',
                    'entity_type' => 'class_subject',
                    'entity_id' => null,
                    'data' => [
                        'subject_name' => $subjectName,
                        'classes' => $slots->map(fn ($s) => $s->classRoom?->full_name)->filter()->unique()->implode(', '),
                        'day_name' => $uniqueDays->map(fn ($d) => TimetableSlot::getDays()[$d] ?? $d)->implode(', '),
                        'issue' => "Combined subject '{$subjectName}' is scheduled on different days for different sections.",
                    ],
                ]);
            }
        }
    }

    /**
     * H14 – Physical subjects (sports, taekwondo, dance) must NOT be in period 5.
     */
    protected function checkPhysicalPeriodPlacement(int $termId, Collection $allSlots): void
    {
        $physicalSubjects = Subject::where('status', 'active')
            ->where(function ($q) {
                foreach ($this->physicalSubjectCodes as $code) {
                    $q->orWhereRaw('LOWER(code) LIKE ?', ['%'.$code.'%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%'.$code.'%']);
                }
            })
            ->pluck('name', 'id');

        if ($physicalSubjects->isEmpty()) {
            return;
        }

        $violations = $allSlots
            ->whereIn('subject_id', $physicalSubjects->keys())
            ->where('period', 5);

        // Group by class + day to reduce noise
        $grouped = $violations->groupBy(fn ($s) => $s->class_room_id.'-'.$s->day);

        foreach ($grouped as $slots) {
            $first = $slots->first();
            $subjectName = $physicalSubjects[$first->subject_id] ?? 'Unknown';
            $className = $first->classRoom?->full_name ?? 'Unknown';
            $dayName = TimetableSlot::getDays()[$first->day] ?? "Day {$first->day}";

            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'physical_period_violation',
                'severity' => 'high',
                'entity_type' => 'classroom',
                'entity_id' => $first->class_room_id,
                'data' => [
                    'class_name' => $className,
                    'subject_name' => $subjectName,
                    'day_name' => $dayName,
                    'wrong_period' => 5,
                    'issue' => "Physical subject '{$subjectName}' must NOT be in period 5 but is scheduled there on {$dayName}",
                ],
            ]);
        }
    }

    // ─── SOFT CONSTRAINTS ─────────────────────────────────────────

    /**
     * S1 – Positional consistency: subjects should retain the same period-order across days.
     */
    protected function checkPositionalConsistency(int $termId, Collection $allSlots): void
    {
        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id)->whereNotNull('subject_id');

            // Build subject order per day (excluding co-curricular)
            $coCurricularIds = Subject::where('type', 'co_curricular')->pluck('id');

            $dayOrders = [];
            foreach (TimetableSlot::getDays() as $dayNum => $dayName) {
                $daySlots = $classSlots
                    ->where('day', $dayNum)
                    ->whereNotIn('subject_id', $coCurricularIds)
                    ->sortBy('period');

                $dayOrders[$dayNum] = $daySlots->pluck('subject_id')->values()->all();
            }

            // Use Sunday (day 0) as reference
            $reference = $dayOrders[0] ?? [];
            if (empty($reference)) {
                continue;
            }

            foreach ($dayOrders as $dayNum => $order) {
                if ($dayNum === 0 || empty($order)) {
                    continue;
                }

                $mismatches = 0;
                $totalComparable = min(count($reference), count($order));
                for ($i = 0; $i < $totalComparable; $i++) {
                    if (($reference[$i] ?? null) !== ($order[$i] ?? null)) {
                        $mismatches++;
                    }
                }

                // Flag if more than 30% of positions differ
                if ($totalComparable > 0 && ($mismatches / $totalComparable) > 0.3) {
                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'positional_consistency',
                        'severity' => 'low',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'day_name' => TimetableSlot::getDays()[$dayNum] ?? "Day {$dayNum}",
                            'mismatches' => $mismatches,
                            'total' => $totalComparable,
                            'percentage' => round(($mismatches / $totalComparable) * 100),
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * S2 – Core subjects (English, Math, Science) should preferably be in the same period slot daily.
     */
    protected function checkCoreSubjectConsistency(int $termId, Collection $allSlots): void
    {
        $coreSubjects = Subject::where('type', 'core')
            ->where('status', 'active')
            ->whereIn(DB::raw('LOWER(name)'), $this->heavySubjectCodes)
            ->get();

        if ($coreSubjects->isEmpty()) {
            return;
        }

        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            foreach ($coreSubjects as $subject) {
                $subjectSlots = $classSlots->where('subject_id', $subject->id);
                $periodsPerDay = $subjectSlots->groupBy('day')->map(fn ($s) => $s->pluck('period')->sort()->first());

                $uniquePeriods = $periodsPerDay->unique()->count();
                $totalDays = $periodsPerDay->count();

                // If the subject appears on 3+ days and uses 3+ different first-period positions
                if ($totalDays >= 3 && $uniquePeriods >= 3) {
                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'core_subject_consistency',
                        'severity' => 'low',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'subject_name' => $subject->name,
                            'distinct_periods' => $uniquePeriods,
                            'days_scheduled' => $totalDays,
                            'issue' => "Core subject '{$subject->name}' is placed in {$uniquePeriods} different period positions across {$totalDays} days",
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * S3 – Avoid scheduling mentally heavy subjects consecutively.
     */
    protected function checkConsecutiveHeavySubjects(int $termId, Collection $allSlots): void
    {
        $heavyIds = Subject::where('status', 'active')
            ->where(function ($q) {
                foreach ($this->heavySubjectCodes as $code) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['%'.$code.'%']);
                }
            })
            ->pluck('id');

        if ($heavyIds->isEmpty()) {
            return;
        }

        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id);

            foreach (TimetableSlot::getDays() as $dayNum => $dayName) {
                $daySlots = $classSlots->where('day', $dayNum)->sortBy('period');
                $previous = null;
                $consecutiveCount = 0;

                foreach ($daySlots as $slot) {
                    if ($heavyIds->contains($slot->subject_id)) {
                        $consecutiveCount++;
                        if ($consecutiveCount >= 2 && $previous) {
                            Conflict::create([
                                'academic_term_id' => $termId,
                                'type' => 'consecutive_heavy',
                                'severity' => 'low',
                                'entity_type' => 'classroom',
                                'entity_id' => $class->id,
                                'data' => [
                                    'class_name' => $class->full_name,
                                    'day_name' => $dayName,
                                    'subject1' => $previous->subject->name ?? 'Unknown',
                                    'subject2' => $slot->subject->name ?? 'Unknown',
                                    'periods' => ($slot->period - 1).' & '.$slot->period,
                                    'issue' => "Heavy subjects '{$previous->subject->name}' and '{$slot->subject->name}' are consecutive",
                                ],
                            ]);
                        }
                    } else {
                        $consecutiveCount = 0;
                    }
                    $previous = $slot;
                }
            }
        }
    }

    /**
     * S4 – Co-curricular subjects should preferably be in middle or last periods (4-8).
     */
    protected function checkCoCurricularPlacement(int $termId, Collection $allSlots): void
    {
        $coCurricularIds = Subject::where('type', 'co_curricular')
            ->where('status', 'active')
            ->pluck('name', 'id');

        if ($coCurricularIds->isEmpty()) {
            return;
        }

        $earlySlots = $allSlots
            ->whereIn('subject_id', $coCurricularIds->keys())
            ->where('period', '<', 4);

        $grouped = $earlySlots->groupBy(fn ($s) => $s->class_room_id.'-'.$s->day);

        foreach ($grouped as $slots) {
            $first = $slots->first();
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'cocurricular_placement',
                'severity' => 'low',
                'entity_type' => 'classroom',
                'entity_id' => $first->class_room_id,
                'data' => [
                    'class_name' => $first->classRoom?->full_name ?? 'Unknown',
                    'day_name' => TimetableSlot::getDays()[$first->day] ?? "Day {$first->day}",
                    'subject_name' => $coCurricularIds[$first->subject_id] ?? 'Unknown',
                    'period' => $slots->pluck('period')->sort()->implode(', '),
                    'issue' => 'Co-curricular subject scheduled in early periods (before period 4)',
                ],
            ]);
        }
    }

    /**
     * S5 – Subject allocations should be balanced across the week (not overloaded on one day).
     *       Soft: prefer not more than 1 period of same subject per day.
     */
    protected function checkSubjectDailyBalance(int $termId, Collection $allSlots): void
    {
        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            $classSlots = $allSlots->where('class_room_id', $class->id)->whereNotNull('subject_id');

            // Group by subject, then check daily distribution
            $bySubject = $classSlots->groupBy('subject_id');

            foreach ($bySubject as $subjectId => $subjectSlots) {
                $dayDistribution = $subjectSlots->groupBy('day')->map(fn ($slots) => $slots->count());
                $maxInOneDay = $dayDistribution->max();

                // Co-curricular allowed 2 per day, so skip those
                $subject = $subjectSlots->first()->subject;
                if ($subject && $subject->type === 'co_curricular') {
                    continue;
                }

                // Soft: flag if a non-co-curricular subject appears 2+ times in a day
                if ($maxInOneDay >= 2) {
                    $worstDay = $dayDistribution->filter(fn ($c) => $c >= 2)->keys()->first();
                    $dayName = TimetableSlot::getDays()[$worstDay] ?? "Day {$worstDay}";

                    Conflict::create([
                        'academic_term_id' => $termId,
                        'type' => 'subject_daily_balance',
                        'severity' => 'medium',
                        'entity_type' => 'classroom',
                        'entity_id' => $class->id,
                        'data' => [
                            'class_name' => $class->full_name,
                            'subject_name' => $subject->name ?? 'Unknown',
                            'day_name' => $dayName,
                            'count' => $maxInOneDay,
                            'issue' => "Subject '{$subject->name}' appears {$maxInOneDay} times on {$dayName} (prefer max 1 per day)",
                        ],
                    ]);
                }
            }
        }
    }

    // ─── Actions ──────────────────────────────────────────────────

    public function refreshCheck(): void
    {
        $this->checkConflicts();

        Notification::make()
            ->title('Conflicts Rechecked')
            ->success()
            ->body('Found '.($this->conflicts['total_conflicts'] ?? 0).' conflict(s)')
            ->send();
    }

    public function autoResolveConflicts(): void
    {
        $data = $this->form->getState();

        if (! isset($data['academic_term_id'])) {
            Notification::make()
                ->title('No Term Selected')
                ->danger()
                ->body('Please select an academic term first.')
                ->send();

            return;
        }

        try {
            $resolver = new ConflictResolverService($data['academic_term_id']);
            $result = $resolver->resolveAllConflicts();

            $this->checkConflicts();

            Notification::make()
                ->title('Conflicts Auto-Resolved')
                ->success()
                ->body('Resolved '.count($result['actions']).' conflict(s). Please review the timetable.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Resolution Failed')
                ->danger()
                ->body('Error: '.$e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('autoResolve')
                ->label('Auto-Resolve Conflicts')
                ->icon('heroicon-m-wrench-screwdriver')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Auto-Resolve Conflicts?')
                ->modalDescription('This will attempt to automatically resolve conflicts by rescheduling slots. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Resolve')
                ->action('autoResolveConflicts')
                ->visible(fn () => isset($this->conflicts) && ($this->conflicts['total_conflicts'] ?? 0) > 0),

            Action::make('refresh')
                ->label('Recheck')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action('refreshCheck'),

            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-m-document-text')
                ->color('primary')
                ->action(function () {
                    Notification::make()
                        ->title('Export Feature')
                        ->info()
                        ->body('Export functionality will be implemented soon.')
                        ->send();
                })
                ->visible(fn () => isset($this->conflicts) && ($this->conflicts['total_conflicts'] ?? 0) > 0),
        ];
    }
}
