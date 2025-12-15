<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\TimetableSlot;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ConflictChecker extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected string $view = 'filament.pages.conflict-checker';

    protected static ?string $navigationLabel = 'Conflict Checker';

    protected static ?string $title = 'Conflict Checker';

    protected static UnitEnum|string|null $navigationGroup = 'Timetable Management';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

        if (!isset($data['academic_term_id'])) {
            $this->conflicts = null;
            return;
        }

        $termId = $data['academic_term_id'];
        
        // Check for teacher conflicts (same teacher, same time, different classes)
        $teacherConflicts = DB::table('timetable_slots as t1')
            ->join('timetable_slots as t2', function($join) use ($termId) {
                $join->on('t1.teacher_id', '=', 't2.teacher_id')
                    ->on('t1.day', '=', 't2.day')
                    ->on('t1.period', '=', 't2.period')
                    ->where('t1.academic_term_id', $termId)
                    ->where('t2.academic_term_id', $termId)
                    ->whereColumn('t1.id', '<', 't2.id'); // Avoid duplicate pairs
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

        // Check for unavailable periods violations
        $unavailableViolations = DB::table('timetable_slots as ts')
            ->join('teachers as t', 'ts.teacher_id', '=', 't.id')
            ->where('ts.academic_term_id', $termId)
            ->whereNotNull('t.unavailable_periods')
            ->select('ts.id', 'ts.day', 'ts.period', 't.name as teacher_name', 't.unavailable_periods')
            ->get()
            ->filter(function($slot) {
                $unavailable = json_decode($slot->unavailable_periods, true) ?? [];
                foreach ($unavailable as $period) {
                    if ($period['day'] == $slot->day && $period['period'] == $slot->period) {
                        return true;
                    }
                }
                return false;
            });

        // Check for overloaded teachers (exceeding max periods per week)
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

        $this->conflicts = [
            'teacher_conflicts' => $teacherConflicts,
            'unavailable_violations' => $unavailableViolations,
            'overloaded_teachers' => $overloadedTeachers,
            'total_conflicts' => $teacherConflicts->count() + $unavailableViolations->count() + $overloadedTeachers->count(),
            'term' => AcademicTerm::find($termId),
        ];
    }

    public function refreshCheck(): void
    {
        $this->checkConflicts();
        
        Notification::make()
            ->title('Conflicts Rechecked')
            ->success()
            ->body('Found ' . ($this->conflicts['total_conflicts'] ?? 0) . ' conflict(s)')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
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
