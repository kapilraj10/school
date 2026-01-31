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
                                    ->placeholder('+1234567890')
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
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Subject Assignment')
                    ->description('Select subjects this teacher can teach')
                    ->schema([
                        Select::make('subject_ids')
                            ->label('Subjects Can Teach')
                            ->multiple()
                            ->options(fn () => Subject::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('Select all subjects this teacher is qualified to teach'),
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
                            ->helperText('Leave empty to allow teaching all classes, or select specific classes'),
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
                            ->required()
                            ->helperText('Click cells to toggle availability. Click headers to toggle entire rows/columns.')
                            ->afterStateHydrated(function (AvailabilityGrid $component, $state, $record) {
                                if ($record) {
                                    $component->state([
                                        'days' => $record->available_days ?? [],
                                        'periods' => $record->available_periods ?? [],
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
        if (isset($data['availability'])) {
            $data['available_days'] = $data['availability']['days'] ?? [];
            $data['available_periods'] = $data['availability']['periods'] ?? [];
            unset($data['availability']);
        }

        return $data;
    }
}
