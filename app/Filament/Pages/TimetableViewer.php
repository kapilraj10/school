<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Room;
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

class TimetableViewer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.timetable-viewer';

    protected static ?string $navigationLabel = 'View Timetable';

    protected static ?string $title = 'Timetable Viewer';

    protected static ?string $navigationGroup = 'View Timetable';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public $timetableData = null;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();
        $firstClass = ClassRoom::active()->first();

        $viewType = request('view_type', 'class');
        $classId = request('class_id');
        $teacherId = request('teacher_id');
        $roomId = request('room_id');
        $termId = request('term_id');

        $selectedTermId = $termId ?: $currentTerm?->id;
        $selectedClassId = $classId ?: $firstClass?->id;

        $this->form->fill([
            'view_type' => $viewType,
            'academic_term_id' => $selectedTermId,
            'class_room_id' => $selectedClassId,
            'teacher_id' => $teacherId,
            'room_id' => $roomId,
        ]);

        if ($selectedTermId) {
            $this->loadTimetable();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('view_type')
                    ->label('View By')
                    ->options([
                        'class' => 'Class',
                        'teacher' => 'Teacher',
                        'room' => 'Room',
                    ])
                    ->default('class')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (): void {
                        $this->timetableData = null;
                        $this->loadTimetable();
                    })
                    ->native(false),

                Select::make('academic_term_id')
                    ->label('Academic Term')
                    ->options(AcademicTerm::query()->orderBy('year', 'desc')->orderBy('term', 'desc')->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable())
                    ->native(false)
                    ->searchable(),

                Select::make('class_room_id')
                    ->label('Class')
                    ->options(ClassRoom::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->full_name]))
                    ->required(fn ($get): bool => ($get('view_type') ?? 'class') === 'class')
                    ->visible(fn ($get): bool => ($get('view_type') ?? 'class') === 'class')
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable())
                    ->native(false)
                    ->searchable(),

                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(Teacher::active()->pluck('name', 'id'))
                    ->required(fn ($get): bool => ($get('view_type') ?? 'class') === 'teacher')
                    ->visible(fn ($get): bool => ($get('view_type') ?? 'class') === 'teacher')
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable())
                    ->native(false)
                    ->searchable(),

                Select::make('room_id')
                    ->label('Room / Lab')
                    ->options(Room::active()->orderBy('name')->pluck('name', 'id'))
                    ->required(fn ($get): bool => ($get('view_type') ?? 'class') === 'room')
                    ->visible(fn ($get): bool => ($get('view_type') ?? 'class') === 'room')
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable())
                    ->native(false)
                    ->searchable(),
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

        $viewType = $data['view_type'] ?? 'class';
        $slots = collect();
        $entity = null;

        if ($viewType === 'class') {
            if (empty($data['class_room_id'])) {
                $this->timetableData = null;

                return;
            }

            $slots = TimetableSlot::where('academic_term_id', $data['academic_term_id'])
                ->where('class_room_id', $data['class_room_id'])
                ->with(['subject', 'teacher', 'combinedPeriod', 'classRoom'])
                ->orderBy('day')
                ->orderBy('period')
                ->get();

            $entity = ClassRoom::find($data['class_room_id']);
        }

        if ($viewType === 'teacher') {
            if (empty($data['teacher_id'])) {
                $this->timetableData = null;

                return;
            }

            $slots = TimetableSlot::where('academic_term_id', $data['academic_term_id'])
                ->where('teacher_id', $data['teacher_id'])
                ->with(['subject', 'teacher', 'combinedPeriod', 'classRoom'])
                ->orderBy('day')
                ->orderBy('period')
                ->get();

            $entity = Teacher::find($data['teacher_id']);
        }

        if ($viewType === 'room') {
            if (empty($data['room_id'])) {
                $this->timetableData = null;

                return;
            }

            $roomId = (int) $data['room_id'];
            $slots = TimetableSlot::query()
                ->where('academic_term_id', $data['academic_term_id'])
                ->whereExists(function ($query) use ($roomId): void {
                    $query->selectRaw('1')
                        ->from('class_subject_settings')
                        ->whereColumn('class_subject_settings.class_room_id', 'timetable_slots.class_room_id')
                        ->whereColumn('class_subject_settings.subject_id', 'timetable_slots.subject_id')
                        ->where('class_subject_settings.room_id', $roomId)
                        ->where('class_subject_settings.is_active', true);
                })
                ->with(['subject', 'teacher', 'combinedPeriod', 'classRoom'])
                ->orderBy('day')
                ->orderBy('period')
                ->get();

            $entity = Room::find($data['room_id']);
        }

        $days = TimetableSlot::getDays();
        $periods = TimetableSlot::getPeriods();

        $organized = [];
        foreach (array_keys($days) as $day) {
            $organized[$day] = [];
            foreach (array_keys($periods) as $period) {
                $slot = $slots->where('day', $day)->where('period', $period)->first();
                $organized[$day][$period] = $slot;
            }
        }

        $this->timetableData = [
            'slots' => $organized,
            'days' => $days,
            'periods' => $periods,
            'viewType' => $viewType,
            'entity' => $entity,
            'term' => AcademicTerm::find($data['academic_term_id']),
            'totalSlots' => $slots->count(),
            'filledSlots' => $slots->whereNotNull('subject_id')->count(),
        ];
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
                ->visible(fn () => isset($this->data['academic_term_id']) && isset($this->data['class_room_id'])),

            Action::make('goToPrintCenter')
                ->label('Export / Print')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->url(function (): string {
                    $viewType = $this->data['view_type'] ?? 'class';

                    return route('filament.admin.pages.print-center', [
                        'print_type' => $viewType,
                        'term_id' => $this->data['academic_term_id'] ?? null,
                        'class_room_id' => $this->data['class_room_id'] ?? null,
                        'teacher_id' => $this->data['teacher_id'] ?? null,
                        'room_id' => $this->data['room_id'] ?? null,
                    ]);
                })
                ->visible(fn () => isset($this->data['academic_term_id'])),
        ];
    }
}
