<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
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

        $classId = request('class_id');
        $termId = request('term_id');

        $selectedTermId = $termId ?: $currentTerm?->id;
        $selectedClassId = $classId ?: $firstClass?->id;

        $this->form->fill([
            'academic_term_id' => $selectedTermId,
            'class_room_id' => $selectedClassId,
        ]);

        if ($selectedTermId && $selectedClassId) {
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

                Select::make('class_room_id')
                    ->label('Class')
                    ->options(ClassRoom::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->full_name]))
                    ->required()
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

        if (! isset($data['academic_term_id']) || ! isset($data['class_room_id'])) {
            $this->timetableData = null;

            return;
        }

        $slots = TimetableSlot::where('academic_term_id', $data['academic_term_id'])
            ->where('class_room_id', $data['class_room_id'])
            ->with(['subject', 'teacher', 'combinedPeriod'])
            ->orderBy('day')
            ->orderBy('period')
            ->get();

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
            'class' => ClassRoom::find($data['class_room_id']),
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
                ->label('Export PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->url(fn () => route('filament.admin.pages.print-center'))
                ->visible(fn () => isset($this->data['academic_term_id']) && isset($this->data['class_room_id'])),
        ];
    }
}
