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

    public $currentDay = 0; // 0 = Sunday

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();

        // Set current day based on today (0 = Sunday)
        $this->currentDay = (int) date('w');

        $termId = request('term_id');
        $selectedTermId = $termId ?: $currentTerm?->id;

        $this->form->fill([
            'academic_term_id' => $selectedTermId,
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
            ])
            ->statePath('data');
    }

    public function loadTimetable(): void
    {
        $data = $this->form->getState();

        if (! isset($data['academic_term_id'])) {
            $this->timetableData = null;

            return;
        }

        // Get all active classes
        $classes = ClassRoom::active()
            ->orderBy('name')
            ->orderBy('section')
            ->get();

        // Get all slots for the selected day and term
        $slots = TimetableSlot::where('academic_term_id', $data['academic_term_id'])
            ->where('day', $this->currentDay)
            ->with(['subject', 'teacher', 'classRoom', 'combinedPeriod'])
            ->orderBy('period')
            ->get();

        // Organize data by class and period
        $organized = [];
        foreach ($classes as $class) {
            $organized[$class->id] = [
                'class' => $class,
                'periods' => [],
            ];

            for ($period = 1; $period <= 8; $period++) {
                $slot = $slots->where('class_room_id', $class->id)
                    ->where('period', $period)
                    ->first();

                $organized[$class->id]['periods'][$period] = $slot;
            }
        }

        $this->timetableData = [
            'classes' => $organized,
            'day' => TimetableSlot::$days[$this->currentDay] ?? 'Unknown',
            'dayNum' => $this->currentDay,
            'periods' => TimetableSlot::$periods,
            'term' => AcademicTerm::find($data['academic_term_id']),
            'totalSlots' => $slots->count(),
            'filledSlots' => $slots->whereNotNull('subject_id')->count(),
        ];
    }

    public function previousDay(): void
    {
        $this->currentDay = ($this->currentDay - 1 + 6) % 6;
        $this->loadTimetable();

        Notification::make()
            ->title('Switched to '.(TimetableSlot::$days[$this->currentDay] ?? 'Unknown'))
            ->success()
            ->send();
    }

    public function nextDay(): void
    {
        $this->currentDay = ($this->currentDay + 1) % 6;
        $this->loadTimetable();

        Notification::make()
            ->title('Switched to '.(TimetableSlot::$days[$this->currentDay] ?? 'Unknown'))
            ->success()
            ->send();
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
