<?php

namespace App\Filament\Pages;

use App\Models\ClassRoom;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Services\TeacherRequirementAnalyzer;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class TeacherRequirements extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string $view = 'filament.pages.teacher-requirements';

    protected static ?string $navigationLabel = 'Teacher Requirements';

    protected static ?string $title = 'Teacher Requirements Analyzer';

    protected static ?string $navigationGroup = 'Timetable Settings';

    protected static ?int $navigationSort = 5;

    public ?string $viewMode = 'by_class';

    public ?int $selectedClassId = null;

    public ?string $filterSubject = '';

    public ?string $filterType = '';

    public ?string $filterSingleCombined = '';

    public ?array $analysisData = null;

    public ?array $settings = null;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->loadAnalysis();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('viewMode')
                    ->label('View Mode')
                    ->options([
                        'by_class' => 'By Class (All Subjects)',
                        'by_subject' => 'By Subject (All Classes)',
                        'single_class' => 'Single Class Details',
                    ])
                    ->default('by_class')
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadAnalysis())
                    ->native(false),

                Select::make('selectedClassId')
                    ->label('Select Class')
                    ->options(function () {
                        return ClassRoom::active()
                            ->get()
                            ->sortBy(function ($class) {
                                return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
                            })
                            ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                            ->toArray();
                    })
                    ->visible(fn () => $this->viewMode === 'single_class')
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadAnalysis())
                    ->searchable()
                    ->native(false),
            ]);
    }

    public function loadAnalysis(): void
    {
        $analyzer = new TeacherRequirementAnalyzer;
        $this->settings = $analyzer->getSettings();

        if ($this->viewMode === 'by_subject') {
            $this->analysisData = $analyzer->analyzeBySubject()->toArray();
        } elseif ($this->viewMode === 'single_class' && $this->selectedClassId) {
            $this->analysisData = $analyzer->analyzeForClass($this->selectedClassId)->toArray();
        } else {
            $this->analysisData = $analyzer->analyzeAll()->toArray();
        }
    }

    public function getFilteredData(): array
    {
        $data = $this->analysisData ?? [];

        if ($this->filterSubject) {
            $data = array_filter($data, function ($item) {
                return stripos($item['subject_name'] ?? '', $this->filterSubject) !== false;
            });
        }

        if ($this->filterType) {
            $data = array_filter($data, function ($item) {
                return ($item['subject_type'] ?? 'core') === $this->filterType;
            });
        }

        if ($this->filterSingleCombined) {
            $data = array_filter($data, function ($item) {
                return ($item['single_combined'] ?? 'single') === $this->filterSingleCombined;
            });
        }

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => ClassRoom::query()->limit(0)) // Dummy query, we use custom data
            ->columns([])
            ->paginated(false);
    }

    public function getAnalysisResults(): array
    {
        return $this->analysisData ?? [];
    }

    public function getSettingsInfo(): array
    {
        return $this->settings ?? [];
    }

    public function getViewMode(): string
    {
        return $this->viewMode ?? 'by_class';
    }

    /**
     * @return array<int, array{text: string, example?: string}>
     */
    public function getHardRequirements(): array
    {
        $periodsPerDay = TimetableSlot::getPeriodsPerDay();
        $days = TimetableSlot::getDays();
        $dayCount = count($days);
        $totalPeriods = $periodsPerDay * $dayCount;
        $dayList = implode(' to ', [reset($days), end($days)]);
        $maxSameSubject = (int) TimetableSetting::get('max_same_subject_per_day', 2);

        return [
            ['text' => "There should be {$periodsPerDay} periods per day, {$totalPeriods} per week."],
            ['text' => "In a week there are {$dayCount} active days ({$dayList})."],
            ['text' => 'There are 3 types of subjects: compulsory, optional, and co-curricular.'],
            ['text' => 'No two co-curricular subjects should be taught in a single day.'],
            ['text' => "Not more than {$maxSameSubject} periods of 1 subject in a day."],
            [
                'text' => 'A co-curricular subject may be scheduled for at most two periods per day, and only if both periods are for the same subject and are consecutive.',
                'example' => 'e.g. dance–dance is allowed; dance–sport and dance–math–dance are not allowed.',
            ],
            ['text' => 'Each subject must satisfy its minimum and maximum weekly period allocation.'],
            ['text' => 'Subject allocations must be balanced across the week (no subject overloaded on a single day).'],
            ['text' => 'A teacher cannot be assigned to more than one class or section in the same period.'],
            ['text' => 'Teacher workload must not exceed maximum allowed periods per day. Max allocation is 7 periods per day.'],
            ['text' => "All {$totalPeriods} periods should be taught in a week."],
            ['text' => 'There should be no empty slots in the timetable.'],
            [
                'text' => 'Combined subjects must be scheduled for the same grade on the same day and in the same periods.',
                'example' => '✔ Sport on Tuesday, periods 5–6 for Class 1 Sections A & B. ✘ Sport on Tuesday, periods 5–6 for Class 1 and Class 2.',
            ],
            [
                'text' => 'No physical period (sports, taekwondo, dance) in the 5th period.',
            ],
        ];
    }

    /**
     * @return array<int, array{text: string, example?: string}>
     */
    public function getSoftRequirements(): array
    {
        return [
            [
                'text' => 'Timetable should maintain positional consistency across days.',
                'example' => 'e.g. If Sunday order is English, Math, Science, Nepali, Serofero, GK, Computer, English — other days should retain the same subject order, replacing only the co-curricular subject.',
            ],
            ['text' => 'Not more than 1 period of 1 subject in a day (preferred).'],
            ['text' => 'Core subjects (English, Math, Science) should preferably be scheduled in the same period slots daily.'],
            [
                'text' => 'Avoid scheduling mentally heavy subjects consecutively.',
                'example' => 'e.g. Math → Science → Math should be avoided.',
            ],
            ['text' => 'Co-curricular subjects should preferably be placed in middle or last periods.'],
        ];
    }
}
