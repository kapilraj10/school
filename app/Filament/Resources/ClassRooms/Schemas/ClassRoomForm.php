<?php

namespace App\Filament\Resources\ClassRooms\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class ClassRoomForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Class Information')
                    ->description('Enter the basic information for the class')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Class Name')
                                    ->required()
                                    ->placeholder('e.g., Class 1, Class 2')
                                    ->maxLength(100)
                                    ->helperText('Enter the class name without section')
                                    ->columnSpan(1),

                                Select::make('section')
                                    ->label('Section')
                                    ->options([
                                        'A' => 'Section A',
                                        'B' => 'Section B',
                                        'C' => 'Section C',
                                        'D' => 'Section D',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->native(false)
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
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Timetable Configuration')
                    ->description('Configure the weekly schedule for this class')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('weekly_periods')
                                    ->label('Weekly Periods')
                                    ->numeric()
                                    ->default(30)
                                    ->required()
                                    ->minValue(20)
                                    ->maxValue(40)
                                    ->suffix('periods')
                                    ->helperText('Total number of periods per week')
                                    ->columnSpan(1),

                                TextInput::make('total_subjects')
                                    ->label('Total Subjects')
                                    ->numeric()
                                    ->default(7)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(15)
                                    ->suffix('subjects')
                                    ->helperText('Number of subjects taught in this class')
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
