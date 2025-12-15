<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    
    protected static ?string $navigationLabel = 'Subjects';
    
    protected static UnitEnum|string|null $navigationGroup = 'Academic Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subject Information')
                ->description('Enter the subject details')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Mathematics'),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., MATH101'),
                        Select::make('type')
                            ->options([
                                'core' => 'Core Subject',
                                'elective' => 'Elective',
                                'co_curricular' => 'Co-curricular',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('weekly_periods')
                            ->label('Weekly Periods')
                            ->numeric()
                            ->required()
                            ->default(4)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Number of periods per week'),
                        Select::make('level')
                            ->options([
                                'all' => 'All Levels',
                                'junior' => 'Junior',
                                'senior' => 'Senior',
                            ])
                            ->default('all')
                            ->native(false)
                            ->helperText('Which class levels take this subject'),
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'core' => 'success',
                        'elective' => 'warning',
                        'co_curricular' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('weekly_periods')
                    ->label('Periods/Week')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'core' => 'Core Subject',
                        'elective' => 'Elective',
                        'co_curricular' => 'Co-curricular',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
