<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationLabel = 'Subjects';
    
    protected static ?string $navigationGroup = 'Academic Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Subject Information')
                ->description('Enter the subject details')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Mathematics'),
                        Select::make('class_range')
                            ->label('Class Range')
                            ->options([
                                '1 - 4' => 'Class 1-4',
                                '5 - 7' => 'Class 5-7',
                                '8' => 'Class 8',
                                '9 - 10' => 'Class 9-10',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Which class range this subject applies to'),
                        Select::make('single_combined')
                            ->label('Period Type')
                            ->options([
                                'single' => 'Single (One Class)',
                                'combined' => 'Combined (Multiple Classes)',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Whether periods are for single class or combined'),
                        TextInput::make('weekly_periods')
                            ->label('Weekly Periods (Target)')
                            ->numeric()
                            ->required()
                            ->default(4)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Target number of periods per week'),
                        TextInput::make('min_periods_per_week')
                            ->label('Minimum Periods')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Minimum periods that must be assigned'),
                        TextInput::make('max_periods_per_week')
                            ->label('Maximum Periods')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Maximum periods allowed per week'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('single_combined')
                    ->label('Period Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'single' => 'success',
                        'combined' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),
                Tables\Columns\TextColumn::make('weekly_periods')
                    ->label('Periods/Week')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('min_periods_per_week')
                    ->label('Min')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('max_periods_per_week')
                    ->label('Max')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->groups([
                Tables\Grouping\Group::make('class_range')
                    ->label('Class Range')
                    ->collapsible(),
            ])
            ->defaultGroup('class_range')
            ->filters([
                Tables\Filters\SelectFilter::make('class_range')
                    ->label('Class Range')
                    ->options([
                        '1 - 4' => 'Class 1-4',
                        '5 - 7' => 'Class 5-7',
                        '8' => 'Class 8',
                        '9 - 10' => 'Class 9-10',
                    ]),
                Tables\Filters\SelectFilter::make('single_combined')
                    ->label('Period Type')
                    ->options([
                        'single' => 'Single',
                        'combined' => 'Combined',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
