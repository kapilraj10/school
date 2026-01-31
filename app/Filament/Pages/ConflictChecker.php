<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\Conflict;
use App\Services\ConflictResolverService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    public function checkConflicts(): void
    {
        $data = $this->form->getState();

        if (! isset($data['academic_term_id'])) {
            $this->conflicts = null;

            return;
        }

        $termId = $data['academic_term_id'];

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

        $unavailableViolations = DB::table('timetable_slots as ts')
            ->join('teachers as t', 'ts.teacher_id', '=', 't.id')
            ->where('ts.academic_term_id', $termId)
            ->select('ts.id', 'ts.day', 'ts.period', 't.name as teacher_name', 't.available_days', 't.available_periods')
            ->get()
            ->filter(function ($slot) {
                // Map numeric days to day names
                $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                $dayName = $dayNames[$slot->day] ?? null;

                // Check if teacher is unavailable on this day or period
                $availableDays = json_decode($slot->available_days, true) ?? [];
                $availablePeriods = json_decode($slot->available_periods, true) ?? [];

                // If day is not in available_days, it's a violation
                if (! empty($availableDays) && $dayName && ! in_array($dayName, $availableDays)) {
                    return true;
                }

                // If period is not in available_periods, it's a violation
                if (! empty($availablePeriods) && ! in_array($slot->period, $availablePeriods, true) && ! in_array((string) $slot->period, $availablePeriods, true)) {
                    return true;
                }

                return false;
            });

        $overloadedTeachers = DB::table('timetable_slots as ts')
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

        // Check classroom conflicts
        $classroomConflicts = $this->checkClassroomConflicts($termId);

        // Check daily teacher overload
        $dailyOverloads = $this->checkDailyOverload($termId);

        // Check class subject settings violations
        $classSubjectViolations = $this->checkClassSubjectSettingsViolations($termId);

        // Truncate old conflicts for this term
        Conflict::truncateForTerm($termId);

        // Save teacher conflicts
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

        // Save unavailable violations
        foreach ($unavailableViolations as $violation) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'unavailable_violation',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => null,
                'data' => (array) $violation,
            ]);
        }

        // Save overloaded teachers
        foreach ($overloadedTeachers as $teacher) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'overloaded_teacher',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => $teacher->id,
                'data' => (array) $teacher,
            ]);
        }

        // Save classroom conflicts
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

        // Save daily overload violations
        foreach ($dailyOverloads as $overload) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'daily_overload',
                'severity' => 'high',
                'entity_type' => 'teacher',
                'entity_id' => $overload['teacher_id'],
                'data' => $overload,
            ]);
        }

        // Save min period violations
        foreach ($classSubjectViolations['min_period_violations'] as $violation) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'min_period_violation',
                'severity' => 'medium',
                'entity_type' => 'class_subject',
                'entity_id' => null,
                'data' => $violation,
            ]);
        }

        // Save max period violations
        foreach ($classSubjectViolations['max_period_violations'] as $violation) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'max_period_violation',
                'severity' => 'medium',
                'entity_type' => 'class_subject',
                'entity_id' => null,
                'data' => $violation,
            ]);
        }

        // Save combined period violations
        foreach ($classSubjectViolations['combined_period_violations'] as $violation) {
            Conflict::create([
                'academic_term_id' => $termId,
                'type' => 'combined_period_violation',
                'severity' => 'medium',
                'entity_type' => 'class_subject',
                'entity_id' => null,
                'data' => $violation,
            ]);
        }

        // Load conflicts from database
        $this->conflicts = Conflict::getGroupedByType($termId);
        $this->conflicts['term'] = AcademicTerm::find($termId);
    }

    protected function checkClassroomConflicts(int $termId)
    {
        // This query finds when the SAME classroom is assigned to DIFFERENT classes at the same time
        // Note: In timetable_slots, class_room_id refers to the CLASS (not physical classroom)
        // So we need to check if different classes (class_room_id) are scheduled at same day/period
        // This would indicate a scheduling conflict where a class is double-booked

        // Actually, let's check for physical classroom conflicts by looking at the classroom field if it exists
        // For now, we'll look for cases where same physical space would be needed

        return DB::table('timetable_slots as t1')
            ->join('timetable_slots as t2', function ($join) use ($termId) {
                $join->on('t1.day', '=', 't2.day')
                    ->on('t1.period', '=', 't2.period')
                    ->where('t1.academic_term_id', $termId)
                    ->where('t2.academic_term_id', $termId)
                    ->whereColumn('t1.id', '<', 't2.id')
                    ->whereColumn('t1.class_room_id', '=', 't2.class_room_id'); // Same class, different subjects
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
    }

    protected function checkDailyOverload(int $termId): array
    {
        $violations = [];

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

        foreach ($dailyAssignments as $assignment) {
            if ($assignment->max_periods_per_day > 0 && $assignment->daily_periods > $assignment->max_periods_per_day) {
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $violations[] = [
                    'teacher_id' => $assignment->teacher_id,
                    'teacher_name' => $assignment->teacher_name,
                    'day' => $assignment->day,
                    'day_name' => $dayNames[$assignment->day] ?? "Day {$assignment->day}",
                    'assigned_periods' => $assignment->daily_periods,
                    'max_periods' => $assignment->max_periods_per_day,
                    'excess' => $assignment->daily_periods - $assignment->max_periods_per_day,
                ];
            }
        }

        return $violations;
    }

    protected function checkClassSubjectSettingsViolations(int $termId): array
    {
        $minPeriodViolations = collect();
        $maxPeriodViolations = collect();
        $combinedPeriodViolations = collect();

        // Get all timetable slots grouped by class and subject
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
            // Get the class subject setting
            $setting = \App\Models\ClassSubjectSetting::where('class_room_id', $slot->class_room_id)
                ->where('subject_id', $slot->subject_id)
                ->where('is_active', true)
                ->first();

            if (! $setting) {
                continue;
            }

            $className = $slot->class_name.($slot->class_section ? ' - '.$slot->class_section : '');

            // Check minimum periods per week
            if ($slot->assigned_periods < $setting->min_periods_per_week) {
                $minPeriodViolations->push([
                    'class_name' => $className,
                    'subject_name' => $slot->subject_name,
                    'assigned' => $slot->assigned_periods,
                    'minimum' => $setting->min_periods_per_week,
                    'deficit' => $setting->min_periods_per_week - $slot->assigned_periods,
                ]);
            }

            // Check maximum periods per week
            if ($slot->assigned_periods > $setting->max_periods_per_week) {
                $maxPeriodViolations->push([
                    'class_name' => $className,
                    'subject_name' => $slot->subject_name,
                    'assigned' => $slot->assigned_periods,
                    'maximum' => $setting->max_periods_per_week,
                    'excess' => $slot->assigned_periods - $setting->max_periods_per_week,
                ]);
            }

            // Check combined period constraints
            if ($setting->single_combined === 'combined') {
                // Fetch period details for this specific class-subject combination
                $periods = DB::table('timetable_slots')
                    ->where('academic_term_id', $termId)
                    ->where('class_room_id', $slot->class_room_id)
                    ->where('subject_id', $slot->subject_id)
                    ->orderBy('day')
                    ->orderBy('period')
                    ->select('day', 'period', 'is_combined')
                    ->get();

                $violations = $this->checkCombinedPeriodAdjacency($periods);

                if (! empty($violations)) {
                    $combinedPeriodViolations->push([
                        'class_name' => $className,
                        'subject_name' => $slot->subject_name,
                        'issue' => 'Combined subject must have adjacent periods',
                        'details' => implode('; ', $violations),
                    ]);
                }
            }
        }

        return [
            'min_period_violations' => $minPeriodViolations,
            'max_period_violations' => $maxPeriodViolations,
            'combined_period_violations' => $combinedPeriodViolations,
        ];
    }

    protected function checkCombinedPeriodAdjacency($periods): array
    {
        $violations = [];
        $dayPeriods = [];

        // Group periods by day
        foreach ($periods as $period) {
            $day = $period->day;
            $periodNumber = $period->period;

            if (! isset($dayPeriods[$day])) {
                $dayPeriods[$day] = [];
            }
            $dayPeriods[$day][] = (int) $periodNumber;
        }

        // Check each day for non-adjacent combined periods
        foreach ($dayPeriods as $day => $periodNumbers) {
            if (count($periodNumbers) < 2) {
                continue;
            }

            // Sort by period number
            sort($periodNumbers);

            // Check for gaps between periods on the same day
            for ($i = 0; $i < count($periodNumbers) - 1; $i++) {
                $currentPeriod = $periodNumbers[$i];
                $nextPeriod = $periodNumbers[$i + 1];

                if ($nextPeriod - $currentPeriod > 1) {
                    $dayName = \App\Models\TimetableSlot::$days[$day] ?? "Day $day";
                    $violations[] = "Non-adjacent periods on {$dayName}: Period {$currentPeriod} and {$nextPeriod}";
                }
            }
        }

        return $violations;
    }

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
                ->visible(fn () => isset($this->conflicts) && $this->conflicts['total_conflicts'] > 0),

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
                ->visible(fn () => isset($this->conflicts) && $this->conflicts['total_conflicts'] > 0),
        ];
    }
}
