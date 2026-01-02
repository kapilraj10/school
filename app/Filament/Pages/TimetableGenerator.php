<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Services\TimetableGeneratorService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class TimetableGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.timetable-generator';

    protected static ?string $navigationLabel = 'Generate Timetable';

    protected static ?string $title = 'Timetable Generator';

    protected static ?string $navigationGroup = 'Timetable Management';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public ?array $generationResult = null;

    public function mount(): void
    {
        $this->form->fill([
            'academic_term_id' => AcademicTerm::where('is_active', true)->first()?->id,
            'respect_teacher_availability' => true,
            'avoid_consecutive_subjects' => true,
            'balance_daily_load' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Select Classes')
                        ->description('Choose classes to generate timetable for')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            Select::make('academic_term_id')
                                ->label('Academic Term')
                                ->options(AcademicTerm::query()->orderBy('year', 'desc')->orderBy('term', 'desc')->pluck('name', 'id'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('class_ids', []))
                                ->helperText('Select the academic term for timetable generation'),

                            CheckboxList::make('class_ids')
                                ->label('Select Classes')
                                ->options(function (Get $get) {
                                    return ClassRoom::active()
                                        ->get()
                                        ->sortBy(function ($class) {
                                            return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
                                        })
                                        ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                                        ->toArray();
                                })
                                ->required()
                                ->columns(3)
                                ->gridDirection('row')
                                ->live()
                                ->searchable()
                                ->bulkToggleable()
                                ->helperText('Select one or more classes to generate timetables for'),

                            Placeholder::make('selection_stats')
                                ->label('')
                                ->content(function (Get $get) {
                                    $classIds = $get('class_ids') ?? [];
                                    if (empty($classIds)) {
                                        return view('filament.components.empty-selection');
                                    }

                                    $classes = ClassRoom::whereIn('id', $classIds)
                                        ->select('id', 'name', 'section', 'weekly_periods', 'total_subjects')
                                        ->get();

                                    $totalPeriods = $classes->sum('weekly_periods');
                                    $totalSubjects = $classes->sum('total_subjects');

                                    // Get subject distribution for selected classes
                                    $relevantRanges = $this->getRelevantRangesForClasses($classes);

                                    $subjectStats = Subject::active()
                                        ->whereIn('class_range', $relevantRanges)
                                        ->count();

                                    return view('filament.components.selection-stats', [
                                        'classCount' => count($classIds),
                                        'totalPeriods' => $totalPeriods,
                                        'totalSubjects' => $totalSubjects,
                                        'subjectCount' => $subjectStats,
                                        'classes' => $classes,
                                    ]);
                                }),
                        ]),

                    Wizard\Step::make('Configure Settings')
                        ->description('Set generation preferences')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Toggle::make('respect_teacher_availability')
                                        ->label('Respect Teacher Availability')
                                        ->default(true)
                                        ->inline(false)
                                        ->helperText('Skip periods when teachers are not available'),

                                    Toggle::make('avoid_consecutive_subjects')
                                        ->label('Avoid Consecutive Same Subjects')
                                        ->default(true)
                                        ->inline(false)
                                        ->helperText('Try not to schedule same subject consecutively'),

                                    Toggle::make('balance_daily_load')
                                        ->label('Balance Daily Load')
                                        ->default(true)
                                        ->inline(false)
                                        ->helperText('Distribute subjects evenly across the week'),

                                    Toggle::make('clear_existing')
                                        ->label('Clear Existing Timetables')
                                        ->default(true)
                                        ->inline(false)
                                        ->helperText('Remove existing timetables for selected classes'),
                                ]),

                            Select::make('priority_subjects')
                                ->label('High Priority Subjects')
                                ->multiple()
                                ->options(function (Get $get) {
                                    $classIds = $get('../../class_ids') ?? [];
                                    if (empty($classIds)) {
                                        return Subject::active()
                                            ->select('id', 'name')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    }

                                    $classes = ClassRoom::whereIn('id', $classIds)
                                        ->select('name')
                                        ->get();

                                    $relevantRanges = $this->getRelevantRangesForClasses($classes);

                                    return Subject::active()
                                        ->whereIn('class_range', $relevantRanges)
                                        ->select('id', 'name')
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->helperText(function (Get $get) {
                                    $classIds = $get('../../class_ids') ?? [];

                                    return empty($classIds)
                                        ? 'Select classes first to see relevant subjects'
                                        : 'These subjects will be scheduled first (optional)';
                                }),
                        ]),

                    Wizard\Step::make('Review & Generate')
                        ->description('Review settings and generate timetable')
                        ->icon('heroicon-m-check-circle')
                        ->schema([
                            Placeholder::make('summary')
                                ->label('Generation Summary')
                                ->content(function (Get $get) {
                                    $termId = $get('academic_term_id');
                                    $classIds = $get('class_ids') ?? [];

                                    $term = $termId ? AcademicTerm::find($termId) : null;
                                    $classes = ClassRoom::whereIn('id', $classIds)
                                        ->select('id', 'name', 'section', 'weekly_periods', 'total_subjects')
                                        ->get();
                                    $settings = $get();

                                    // Get additional validation info
                                    $validationData = [];
                                    if (! empty($classIds)) {
                                        $relevantRanges = $this->getRelevantRangesForClasses($classes);

                                        $validationData['availableSubjects'] = Subject::active()
                                            ->whereIn('class_range', $relevantRanges)
                                            ->count();

                                        $validationData['activeTeachers'] = \App\Models\Teacher::where('status', 'active')->count();

                                        $validationData['totalPeriodsToGenerate'] = $classes->sum('weekly_periods');

                                        $validationData['prioritySubjects'] = ! empty($settings['priority_subjects'])
                                            ? Subject::whereIn('id', $settings['priority_subjects'])
                                                ->select('name')
                                                ->pluck('name')
                                                ->toArray()
                                            : [];
                                    }

                                    return view('filament.components.generation-summary', [
                                        'term' => $term,
                                        'classes' => $classes,
                                        'settings' => $settings,
                                        'validation' => $validationData,
                                    ]);
                                }),
                        ]),
                ])
                    ->persistStepInQueryString()
                    ->submitAction(view('filament.components.wizard-submit-action')),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function generateTimetable(): void
    {
        try {
            $data = $this->form->getState();

            if (empty($data['class_ids'])) {
                Notification::make()
                    ->title('No Classes Selected')
                    ->warning()
                    ->body('Please select at least one class to generate a timetable.')
                    ->send();

                return;
            }

            $service = new TimetableGeneratorService;
            $result = $service->generate(
                $data['class_ids'],
                $data['academic_term_id'],
                $data
            );

            $this->generationResult = $result;

            if (! empty($result['success'])) {
                Notification::make()
                    ->title('Timetable Generated Successfully!')
                    ->success()
                    ->body(sprintf(
                        'Generated %d slots for %d classes with %d teachers assigned.',
                        $result['statistics']['total_slots'] ?? 0,
                        $result['statistics']['classes_generated'] ?? 0,
                        $result['statistics']['teachers_used'] ?? 0
                    ))
                    ->duration(8000)
                    ->send();

                if (! empty($result['warnings'])) {
                    foreach (array_slice($result['warnings'], 0, 3) as $warning) {
                        Notification::make()
                            ->title('Generation Warning')
                            ->warning()
                            ->body($warning)
                            ->send();
                    }
                }

                // Redirect to the timetable viewer page after success
                $this->js('setTimeout(() => window.location.href = "'.route('filament.admin.pages.timetable-viewer').'", 2000)');
            } else {
                Notification::make()
                    ->title('Generation Failed')
                    ->danger()
                    ->body('There were errors during generation. Check the details below.')
                    ->persistent()
                    ->send();

                foreach (array_slice($result['errors'] ?? [], 0, 3) as $error) {
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body($error)
                        ->persistent()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Log::error('Timetable generation error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            Notification::make()
                ->title('Unexpected Error')
                ->danger()
                ->body('An unexpected error occurred during timetable generation. Please try again later.')
                ->persistent()
                ->send();
        }
    }

    /**
     * Get relevant class ranges for the given classes
     */
    protected function getRelevantRangesForClasses($classes): array
    {
        return $classes->pluck('name')->map(function ($name) {
            $classNum = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
            if ($classNum >= 1 && $classNum <= 4) {
                return '1 - 4';
            }
            if ($classNum >= 5 && $classNum <= 7) {
                return '5 - 7';
            }
            if ($classNum == 8) {
                return '8';
            }
            if ($classNum >= 9 && $classNum <= 10) {
                return '9 - 10';
            }

            return null;
        })->filter()->unique()->values()->toArray();
    }
}
