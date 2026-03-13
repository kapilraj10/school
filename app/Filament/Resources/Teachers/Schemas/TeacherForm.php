<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Filament\Forms\Components\AvailabilityGrid;
use App\Models\ClassRoom;
use App\Models\Subject;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;

class TeacherForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Enter the teacher\'s basic information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g., John Doe')
                                    ->columnSpan(1),

                                TextInput::make('employee_id')
                                    ->label('Employee ID')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('e.g., EMP001')
                                    ->columnSpan(1),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(100)
                                    ->placeholder('teacher@school.com')
                                    ->columnSpan(1),

                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefix('+977')
                                    ->placeholder('98XXXXXXXX')
                                    ->helperText('Enter 10-digit phone number')
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false)
                                    ->hiddenOn('create')
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Class Assignment')
                    ->description('Select classes this teacher is assigned to teach')
                    ->schema([
                        Select::make('class_room_ids')
                            ->label('Assigned Classes')
                            ->multiple()
                            ->options(fn () => ClassRoom::active()
                                ->get()
                                ->sortBy(fn ($class) => $class->name.$class->section, SORT_NATURAL | SORT_FLAG_CASE)
                                ->mapWithKeys(fn ($class) => [$class->id => $class->full_name])
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $selectedClassRoomIds = array_filter((array) ($get('class_room_ids') ?? []));

                                if (empty($selectedClassRoomIds)) {
                                    $set('subject_ids', []);

                                    return;
                                }

                                $allowedSubjectIds = Subject::query()
                                    ->active()
                                    ->whereIn('class_room_id', $selectedClassRoomIds)
                                    ->pluck('id')
                                    ->map(fn ($id) => (int) $id)
                                    ->values()
                                    ->all();

                                $currentSubjectIds = array_map('intval', (array) ($get('subject_ids') ?? []));
                                $validSubjectIds = array_values(array_intersect($currentSubjectIds, $allowedSubjectIds));

                                $set('subject_ids', $validSubjectIds);
                            })
                            ->helperText('Select specific classes to filter available subjects'),
                    ]),

                Section::make('Subject Assignment')
                    ->description('Select subjects this teacher can teach')
                    ->schema([
                        Select::make('subject_ids')
                            ->label('Subjects Can Teach')
                            ->multiple()
                            ->afterStateHydrated(function (Select $component, $state): void {
                                $selectedSubjectIds = array_filter(array_map('intval', (array) ($state ?? [])));

                                if (empty($selectedSubjectIds)) {
                                    return;
                                }

                                $canonicalSubjectIds = Subject::query()
                                    ->with('classRoom')
                                    ->whereIn('id', $selectedSubjectIds)
                                    ->orderBy('name')
                                    ->get()
                                    ->unique(fn (Subject $subject) => $subject->name.' - '.($subject->classRoom?->name ?? 'Unknown Class'))
                                    ->pluck('id')
                                    ->map(fn ($id) => (int) $id)
                                    ->values()
                                    ->all();

                                $component->state($canonicalSubjectIds);
                            })
                            ->options(function (Get $get) {
                                $classRoomIds = array_filter((array) ($get('class_room_ids') ?? []));

                                if (empty($classRoomIds)) {
                                    return [];
                                }

                                return Subject::active()
                                    ->with('classRoom')
                                    ->whereIn('class_room_id', $classRoomIds)
                                    ->orderBy('name')
                                    ->get()
                                    ->map(fn (Subject $subject) => [
                                        'id' => $subject->id,
                                        'label' => $subject->name.' - '.($subject->classRoom?->name ?? 'Unknown Class'),
                                    ])
                                    ->unique('label')
                                    ->mapWithKeys(fn (array $option) => [$option['id'] => $option['label']]);
                            })
                            ->disabled(fn (Get $get): bool => empty(array_filter((array) ($get('class_room_ids') ?? []))))
                            ->required()
                            ->searchable()
                            ->optionsLimit(1000)
                            ->native(false)
                            ->helperText('Select classes first. All active subjects from those classes are shown.'),
                    ]),

                Section::make('Teaching Capacity')
                    ->description('Configure the teacher\'s workload limits')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_periods_per_day')
                                    ->label('Maximum Periods per Day')
                                    ->numeric()
                                    ->default(6)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(8)
                                    ->suffix('periods')
                                    ->helperText('Maximum teaching periods per day')
                                    ->columnSpan(1),

                                TextInput::make('max_periods_per_week')
                                    ->label('Maximum Periods per Week')
                                    ->numeric()
                                    ->default(30)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(48)
                                    ->suffix('periods')
                                    ->helperText('Maximum teaching periods per week')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Teacher Availability')
                    ->description('Specify when this teacher is available to teach')
                    ->schema([
                        AvailabilityGrid::make('availability')
                            ->label('Availability Grid')
                            ->columnSpanFull()
                            ->required()
                            ->helperText('Click cells to toggle availability. Click headers to toggle entire rows/columns.')
                            ->afterStateHydrated(function (AvailabilityGrid $component, $state, $record) {
                                if ($record) {
                                    $matrix = $record->availability_matrix ?? [];
                                    $days = ! empty($matrix) ? array_keys($matrix) : [];
                                    $periods = [];
                                    if (! empty($matrix)) {
                                        foreach ($matrix as $dayPeriods) {
                                            $periods = array_merge($periods, array_keys($dayPeriods));
                                        }
                                        $periods = array_unique($periods);
                                        sort($periods);
                                    }

                                    $component->state([
                                        'days' => $days,
                                        'periods' => $periods,
                                        'matrix' => $matrix,
                                    ]);
                                }
                            })
                            ->dehydrated()
                            ->live(),
                    ])
                    ->collapsible(),
            ])
            ->columns(1);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['subject_ids']) && ! empty($data['class_room_ids'])) {
            $selectedSubjectIds = array_filter(array_map('intval', (array) $data['subject_ids']));
            $selectedClassRoomIds = array_filter(array_map('intval', (array) $data['class_room_ids']));

            $selectedClassesByName = ClassRoom::query()
                ->whereIn('id', $selectedClassRoomIds)
                ->get(['id', 'name'])
                ->groupBy('name')
                ->map(fn ($classes) => $classes->pluck('id')->map(fn ($id) => (int) $id)->all());

            $expandedSubjectIds = Subject::query()
                ->with('classRoom')
                ->whereIn('id', $selectedSubjectIds)
                ->get()
                ->flatMap(function (Subject $subject) use ($selectedClassesByName) {
                    $className = $subject->classRoom?->name;

                    if ($className === null || ! $selectedClassesByName->has($className)) {
                        return [$subject->id];
                    }

                    return Subject::query()
                        ->active()
                        ->where('name', $subject->name)
                        ->whereIn('class_room_id', $selectedClassesByName->get($className))
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                })
                ->unique()
                ->values()
                ->all();

            $data['subject_ids'] = $expandedSubjectIds;
        }

        if (isset($data['availability'])) {
            $availability = $data['availability'];

            // If matrix is provided, use it directly (source of truth from Alpine.js)
            if (! empty($availability['matrix'])) {
                $matrix = $availability['matrix'];

                // Ensure matrix only includes days and periods that are in days/periods arrays
                // (in case days/periods were updated without corresponding matrix update)
                if (! empty($availability['days']) && ! empty($availability['periods'])) {
                    $allowedDays = array_flip($availability['days']);
                    $allowedPeriods = array_flip($availability['periods']);

                    // Add any missing days/periods that should be available
                    foreach ($availability['days'] as $day) {
                        if (! isset($matrix[$day])) {
                            $matrix[$day] = [];
                        }
                        foreach ($availability['periods'] as $period) {
                            if (! isset($matrix[$day][$period])) {
                                $matrix[$day][$period] = true;
                            }
                        }
                    }

                    // Remove days not in the allowed days list
                    foreach (array_keys($matrix) as $day) {
                        if (! isset($allowedDays[$day])) {
                            unset($matrix[$day]);
                        }
                    }
                }

                $data['availability_matrix'] = $matrix;
            }
            // Build matrix from days and periods
            elseif (! empty($availability['days']) && ! empty($availability['periods'])) {
                $matrix = [];
                foreach ($availability['days'] as $day) {
                    $matrix[$day] = [];
                    foreach ($availability['periods'] as $period) {
                        $matrix[$day][$period] = true;
                    }
                }
                $data['availability_matrix'] = $matrix;
            } else {
                $data['availability_matrix'] = [];
            }

            unset($data['availability']);
        }

        return $data;
    }
}
