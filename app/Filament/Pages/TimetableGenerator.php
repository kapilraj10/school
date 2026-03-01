<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

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

    public bool $isGenerating = false;

    public ?string $progressKey = null;

    /** @var array<string, mixed>|null */
    public ?array $progress = null;

    public function getEstimatedGenerationSeconds(): int
    {
        $classCount = count($this->data['class_ids'] ?? []);

        if ($classCount < 1) {
            return 0;
        }

        $avgSecondsPerClass = (float) Cache::get('ga_generation_avg_seconds_per_class', 60.0);
        $avgSecondsPerClass = max(10.0, min(600.0, $avgSecondsPerClass));

        return (int) max(5, round($avgSecondsPerClass * $classCount));
    }

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

                                    $subjectStats = Subject::active()
                                        ->whereIn('class_room_id', $classIds)
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

                                    return Subject::active()
                                        ->whereIn('class_room_id', $classIds)
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

                                    $validationData = [];
                                    if (! empty($classIds)) {
                                        $validationData['availableSubjects'] = Subject::active()
                                            ->whereIn('class_room_id', $classIds)
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
                    ->submitAction(new HtmlString(view('filament.components.wizard-submit-action', [
                        'estimatedGenerationSeconds' => $this->getEstimatedGenerationSeconds(),
                    ])->render())),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function generateTimetable(): void
    {
        $data = $this->form->getState();

        if (empty($data['class_ids'])) {
            Notification::make()
                ->title('No Classes Selected')
                ->warning()
                ->body('Please select at least one class to generate a timetable.')
                ->send();

            return;
        }

        // Create unique keys for this run
        $userId = auth()->id() ?? 'guest';
        $runId = uniqid('timetable_', true);
        $this->progressKey = 'timetable_progress_'.$userId.'_'.$runId;
        $paramsKey = 'timetable_params_'.$userId.'_'.$runId;

        // Save params to cache so the artisan command can read them
        Cache::put($paramsKey, $data, now()->addHours(1));

        // Write initial "queued" state so the UI can show something immediately
        Cache::put($this->progressKey, [
            'status' => 'starting',
            'total' => count($data['class_ids']),
            'completed' => 0,
            'success_count' => 0,
            'total_slots' => 0,
            'class_statuses' => [],
            'current_class' => 'Starting generation process...',
            'term_id' => (int) ($data['academic_term_id'] ?? 0),
            'first_class_id' => $data['class_ids'][0] ?? null,
            'warnings' => [],
            'errors' => [],
        ], now()->addHours(2));

        $this->isGenerating = true;

        // Launch the artisan command as a background process
        $artisan = base_path('artisan');
        $php = PHP_BINARY;
        $cmd = sprintf(
            '%s %s timetable:generate %s %s > /dev/null 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($this->progressKey),
            escapeshellarg($paramsKey)
        );
        shell_exec($cmd);
    }

    public function tick(): void
    {
        if (! $this->isGenerating || ! $this->progressKey) {
            return;
        }

        $progress = Cache::get($this->progressKey);

        if (! is_array($progress)) {
            return;
        }

        $this->progress = $progress;
        $status = $progress['status'] ?? 'running';

        if ($status === 'completed') {
            $this->isGenerating = false;

            $successCount = (int) ($progress['success_count'] ?? 0);
            $totalSlots = (int) ($progress['total_slots'] ?? 0);
            $total = (int) ($progress['total'] ?? 0);

            if ($successCount > 0) {
                Notification::make()
                    ->title('Timetable Generated Successfully!')
                    ->success()
                    ->body(sprintf(
                        'Generated %d slots for %d out of %d classes.',
                        $totalSlots,
                        $successCount,
                        $total
                    ))
                    ->duration(6000)
                    ->send();

                $redirectUrl = route('filament.admin.pages.timetable-viewer', [
                    'term_id' => $progress['term_id'] ?? null,
                    'class_id' => $progress['first_class_id'] ?? null,
                ]);

                $this->js('setTimeout(function(){ window.location.href = '.json_encode($redirectUrl).'; }, 2000);');
            } else {
                Notification::make()
                    ->title('Generation Failed')
                    ->danger()
                    ->body('There were errors during generation.')
                    ->persistent()
                    ->send();
            }
        } elseif ($status === 'failed') {
            $this->isGenerating = false;

            Notification::make()
                ->title('Generation Failed')
                ->danger()
                ->body($progress['error'] ?? 'An unknown error occurred.')
                ->persistent()
                ->send();
        }
    }
}
