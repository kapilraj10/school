<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassRangeResource\Pages;
use App\Models\ClassRange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClassRangeResource extends Resource
{
    protected static ?string $model = ClassRange::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Class Ranges';

    protected static ?string $modelLabel = 'Class Range';

    protected static ?string $navigationGroup = 'Timetable Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Class Range Information')
                    ->description('Define the class range for grouping subjects')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Range Name')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('e.g., 1 - 4, 11 - 12')
                                    ->helperText('Used internally for subject assignment'),

                                Forms\Components\TextInput::make('display_name')
                                    ->label('Display Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g., Class 1-4, Class 11-12')
                                    ->helperText('Shown in dropdowns and UI'),

                                Forms\Components\TextInput::make('start_class')
                                    ->label('Start Class')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(15)
                                    ->helperText('First class number in range'),

                                Forms\Components\TextInput::make('end_class')
                                    ->label('End Class')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(15)
                                    ->helperText('Last class number in range'),
                            ]),
                    ]),

                Forms\Components\Section::make('Period Configuration')
                    ->description('Configure periods for this class range')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('periods_per_day')
                                    ->label('Periods Per Day')
                                    ->numeric()
                                    ->required()
                                    ->default(8)
                                    ->minValue(4)
                                    ->maxValue(12)
                                    ->suffix('periods')
                                    ->helperText('Number of periods per school day'),

                                Forms\Components\TextInput::make('periods_per_week')
                                    ->label('Periods Per Week')
                                    ->numeric()
                                    ->required()
                                    ->default(48)
                                    ->minValue(20)
                                    ->maxValue(72)
                                    ->suffix('periods')
                                    ->helperText('Total periods per week'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Display order in lists'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Enable/disable this class range'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Range')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_class')
                    ->label('Start')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('end_class')
                    ->label('End')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('periods_per_day')
                    ->label('Periods/Day')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('periods_per_week')
                    ->label('Periods/Week')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassRanges::route('/'),
            'create' => Pages\CreateClassRange::route('/create'),
            'edit' => Pages\EditClassRange::route('/{record}/edit'),
        ];
    }
}
