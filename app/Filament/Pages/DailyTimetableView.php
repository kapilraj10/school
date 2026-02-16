<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class DailyTimetableView extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static string $view = 'filament.pages.daily-timetable-view';

    protected static ?string $navigationLabel = 'Daily Timetable';

    protected static ?string $title = 'Daily Timetable View';

    protected static ?string $navigationGroup = 'View Timetable';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public $timetableData = null;

    public $currentDay = 0; // Index into school days

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();

        // Set current day based on today
        $days = TimetableSlot::getDays();
        $todayName = date('l');
        $this->currentDay = array_search($todayName, $days);
        if ($this->currentDay === false) {
            $this->currentDay = array_key_first($days);
        }

        $termId = request('term_id');
        $selectedTermId = $termId ?: $currentTerm?->id;

        $this->form->fill([
            'academic_term_id' => $selectedTermId,
            'view_mode' => 'class',
        ]);

        if ($selectedTermId) {
            $this->loadTimetable();
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
                    ->afterStateUpdated(fn () => $this->loadTimetable())
                    ->native(false)
                    ->searchable(),

                Select::make('view_mode')
                    ->label('View Mode')
                    ->options([
                        'class' => 'Class',
                        'teacher' => 'Teacher',
                    ])
                    ->required()
                    ->default('class')
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable())
                    ->native(false),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadTimetable(): void
    {
        $data = $this->form->getState();

        if (! isset($data['academic_term_id'])) {
            $this->timetableData = null;

            return;
        }

        $viewMode = $data['view_mode'] ?? 'class';

        // Get all slots for the selected day and term
        $slots = TimetableSlot::where('academic_term_id', $data['academic_term_id'])
            ->where('day', $this->currentDay)
            ->with(['subject', 'teacher', 'classRoom', 'combinedPeriod'])
            ->orderBy('period')
            ->get();

        $rows = [];

        if ($viewMode === 'teacher') {
            $teacherIds = $slots
                ->pluck('teacher_id')
                ->filter()
                ->unique()
                ->values();

            $teachers = Teacher::query()
                ->whereIn('id', $teacherIds)
                ->orderBy('name')
                ->get()
                ->keyBy('id');

            $teacherIds = $teacherIds
                ->sortBy(function ($teacherId) use ($slots, $teachers) {
                    $teacherCode = strtolower($teachers->get($teacherId)?->employee_id ?? "teacher-{$teacherId}");

                    $firstClassSlot = $slots
                        ->where('teacher_id', $teacherId)
                        ->filter(fn ($slot) => $slot->classRoom)
                        ->sortBy(fn ($slot) => [
                            (int) filter_var($slot->classRoom->name, FILTER_SANITIZE_NUMBER_INT),
                            strtoupper($slot->classRoom->section),
                            $slot->period,
                        ])
                        ->first();

                    if (! $firstClassSlot || ! $firstClassSlot->classRoom) {
                        return [9999, 'ZZZ', $teacherCode];
                    }

                    return [
                        (int) filter_var($firstClassSlot->classRoom->name, FILTER_SANITIZE_NUMBER_INT),
                        strtoupper($firstClassSlot->classRoom->section),
                        $teacherCode,
                    ];
                })
                ->values();

            $periods = TimetableSlot::getPeriods();
            foreach ($teacherIds as $teacherId) {
                $teacher = $teachers->get($teacherId);
                $teacherLabel = $teacher?->employee_id ?: "Teacher #{$teacherId}";

                $rows[$teacherId] = [
                    'label' => $teacherLabel,
                    'entity_type' => 'teacher',
                    'entity_id' => $teacherId,
                    'periods' => [],
                ];

                foreach (array_keys($periods) as $period) {
                    $slot = $slots->where('teacher_id', $teacherId)
                        ->where('period', $period)
                        ->first();

                    $rows[$teacherId]['periods'][$period] = $slot;
                }
            }
        } else {
            $classes = ClassRoom::active()
                ->get()
                ->sortBy(fn (ClassRoom $class) => [
                    (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT),
                    strtoupper($class->section),
                ]);

            $periods = TimetableSlot::getPeriods();
            foreach ($classes as $class) {
                $rows[$class->id] = [
                    'label' => $class->full_name,
                    'entity_type' => 'class',
                    'entity_id' => $class->id,
                    'periods' => [],
                ];

                foreach (array_keys($periods) as $period) {
                    $slot = $slots->where('class_room_id', $class->id)
                        ->where('period', $period)
                        ->first();

                    $rows[$class->id]['periods'][$period] = $slot;
                }
            }
        }

        $days = TimetableSlot::getDays();
        $this->timetableData = [
            'rows' => $rows,
            'viewMode' => $viewMode,
            'rowHeaderLabel' => $viewMode === 'teacher' ? 'Teacher/Period' : 'Class/Period',
            'cellMetaLabel' => $viewMode === 'teacher' ? 'Class' : 'Teacher',
            'day' => $days[$this->currentDay] ?? 'Unknown',
            'dayNum' => $this->currentDay,
            'periods' => TimetableSlot::getPeriods(),
            'term' => AcademicTerm::find($data['academic_term_id']),
            'totalSlots' => $slots->count(),
            'filledSlots' => $slots->whereNotNull('subject_id')->count(),
        ];
    }

    public function previousDay(): void
    {
        $days = TimetableSlot::getDays();
        $dayKeys = array_keys($days);
        $currentIndex = array_search($this->currentDay, $dayKeys);
        $prevIndex = ($currentIndex - 1 + count($dayKeys)) % count($dayKeys);
        $this->currentDay = $dayKeys[$prevIndex];
        $this->loadTimetable();
    }

    public function nextDay(): void
    {
        $days = TimetableSlot::getDays();
        $dayKeys = array_keys($days);
        $currentIndex = array_search($this->currentDay, $dayKeys);
        $nextIndex = ($currentIndex + 1) % count($dayKeys);
        $this->currentDay = $dayKeys[$nextIndex];
        $this->loadTimetable();
    }

    public function setDay(int $day): void
    {
        $days = TimetableSlot::getDays();
        if (! array_key_exists($day, $days)) {
            return;
        }

        $this->currentDay = $day;
        $this->loadTimetable();
    }

    public function refreshTimetable(): void
    {
        $this->loadTimetable();

        Notification::make()
            ->title('Timetable Refreshed')
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
                ->action('refreshTimetable'),

            Action::make('print')
                ->label('Print')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()'])
                ->visible(fn () => isset($this->data['academic_term_id'])),
        ];
    }
}
