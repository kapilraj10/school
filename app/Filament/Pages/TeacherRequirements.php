<?php

namespace App\Filament\Pages;

use App\Models\ClassRoom;
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
            ])
            ->statePath('data');
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
}
