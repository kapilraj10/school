<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Services\TimetableGeneratorService;
use Filament\Actions\Action;
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
                                ->helperText('Select the academic term for timetable generation'),

                            CheckboxList::make('class_ids')
                                ->label('Select Classes')
                                ->options(ClassRoom::active()->get()->mapWithKeys(fn($c) => [$c->id => $c->full_name]))
                                ->required()
                                ->columns(3)
                                ->gridDirection('row')
                                ->helperText('Select one or more classes to generate timetables for'),
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
                                ->options(Subject::active()->orderBy('name')->pluck('name', 'id'))
                                ->helperText('These subjects will be scheduled first (optional)'),
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
                                    $classes = ClassRoom::whereIn('id', $classIds)->get();
                                    $settings = $get();

                                    return view('filament.components.generation-summary', [
                                        'term' => $term,
                                        'classes' => $classes,
                                        'settings' => $settings,
                                    ])->render();
                                }),
                        ]),
                ])
                ->persistStepInQueryString()
                ->submitAction(null),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Timetable')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Generate Timetable')
                ->modalDescription('This will create a new timetable for the selected classes. Existing timetables may be replaced if "Clear Existing" is enabled.')
                ->modalSubmitActionLabel('Yes, Generate')
                ->action('generateTimetable'),
        ];
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

            $service = new TimetableGeneratorService();
            $result = $service->generate(
                $data['class_ids'],
                $data['academic_term_id'],
                $data
            );

            $this->generationResult = $result;

            if (!empty($result['success'])) {
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

                if (!empty($result['warnings'])) {
                    foreach (array_slice($result['warnings'], 0, 3) as $warning) {
                        Notification::make()
                            ->title('Generation Warning')
                            ->warning()
                            ->body($warning)
                            ->send();
                    }
                }

                $this->js('setTimeout(() => window.location.href = "' . route('filament.admin.resources.timetable-slots.index') . '", 2000)');
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
            Log::error('Timetable generation error: ' . $e->getMessage(), [
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
}
