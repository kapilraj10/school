<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TeacherSchedule extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.teacher-schedule';

    protected static ?string $navigationLabel = 'Teacher Schedule';

    protected static ?string $title = 'Teacher Schedule';

    protected static ?string $navigationGroup = 'Timetable Management';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public $scheduleData = null;

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();
        $firstTeacher = Teacher::active()->first();

        $this->form->fill([
            'academic_term_id' => $currentTerm?->id,
            'teacher_id' => $firstTeacher?->id,
        ]);

        if ($currentTerm && $firstTeacher) {
            $this->loadSchedule();
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
                    ->afterStateUpdated(fn () => $this->loadSchedule())
                    ->native(false)
                    ->searchable(),

                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(Teacher::active()->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadSchedule())
                    ->native(false)
                    ->searchable(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadSchedule(): void
    {
        $data = $this->form->getState();

        if (! isset($data['academic_term_id']) || ! isset($data['teacher_id'])) {
            $this->scheduleData = null;

            return;
        }

        $slots = TimetableSlot::where('academic_term_id', $data['academic_term_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->with(['subject', 'classRoom', 'combinedPeriod'])
            ->orderBy('day')
            ->orderBy('period')
            ->get();

        $organized = [];
        for ($day = 1; $day <= 5; $day++) {
            $organized[$day] = [];
            for ($period = 1; $period <= 8; $period++) {
                $slot = $slots->where('day', $day)->where('period', $period)->first();
                $organized[$day][$period] = $slot;
            }
        }

        $teacher = Teacher::find($data['teacher_id']);
        $totalPeriods = $slots->count();
        $periodsPerDay = [];

        foreach (range(1, 5) as $day) {
            $periodsPerDay[$day] = $slots->where('day', $day)->count();
        }

        $this->scheduleData = [
            'slots' => $organized,
            'days' => TimetableSlot::$days,
            'periods' => TimetableSlot::$periods,
            'teacher' => $teacher,
            'term' => AcademicTerm::find($data['academic_term_id']),
            'total_periods' => $totalPeriods,
            'periods_per_day' => $periodsPerDay,
            'max_periods_per_day' => $teacher->max_periods_per_day ?? 6,
            'max_periods_per_week' => $teacher->max_periods_per_week ?? 30,
        ];
    }

    public function refreshSchedule(): void
    {
        $this->loadSchedule();

        Notification::make()
            ->title('Schedule Refreshed')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action('refreshSchedule'),

            Action::make('print')
                ->label('Print Schedule')
                ->icon('heroicon-m-printer')
                ->color('primary')
                ->extraAttributes(['onclick' => 'window.print()'])
                ->visible(fn () => isset($this->data['academic_term_id']) && isset($this->data['teacher_id'])),
        ];
    }
}
