<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CombinedPeriodResource\Pages;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CombinedPeriodResource extends Resource
{
    protected static ?string $model = CombinedPeriod::class;
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    
    protected static ?string $navigationLabel = 'Combined Periods';
    
    protected static UnitEnum|string|null $navigationGroup = 'Timetable Management';
    
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Combined Period Details')
                ->description('Setup a combined period for multiple classes')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Martial Arts Combined'),
                        Select::make('academic_term_id')
                            ->label('Academic Term')
                            ->options(AcademicTerm::orderBy('year', 'desc')->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->searchable(),
                        Select::make('subject_id')
                            ->label('Subject')
                            ->options(Subject::active()->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->searchable(),
                        Select::make('teacher_id')
                            ->label('Teacher')
                            ->options(Teacher::active()->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->searchable(),
                    ]),
                ]),

            Section::make('Schedule & Classes')
                ->description('Configure when and which classes attend')
                ->schema([
                    CheckboxList::make('class_room_ids')
                        ->label('Classes')
                        ->options(ClassRoom::active()->get()->mapWithKeys(fn($c) => [$c->id => $c->full_name]))
                        ->required()
                        ->columns(4)
                        ->helperText('Select all classes that will attend together'),
                    Grid::make(3)->schema([
                        Select::make('day')
                            ->label('Day')
                            ->options(TimetableSlot::$days)
                            ->required()
                            ->native(false),
                        Select::make('period')
                            ->label('Period')
                            ->options(TimetableSlot::$periods)
                            ->required()
                            ->native(false)
                            ->helperText('Combined periods should not be in first period'),
                        Select::make('frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                            ])
                            ->default('weekly')
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
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->sortable(),
                Tables\Columns\TextColumn::make('day')
                    ->formatStateUsing(fn($state) => TimetableSlot::$days[$state] ?? 'N/A')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('period')
                    ->formatStateUsing(fn($state) => "Period {$state}")
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('class_room_ids')
                    ->label('Classes')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return 'None';
                        $count = count($state);
                        return "{$count} " . ($count === 1 ? 'class' : 'classes');
                    }),
                Tables\Columns\TextColumn::make('frequency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'weekly' => 'success',
                        'biweekly' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_term_id')
                    ->label('Academic Term')
                    ->options(AcademicTerm::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::pluck('name', 'id')),
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
            'index' => Pages\ListCombinedPeriods::route('/'),
            'create' => Pages\CreateCombinedPeriod::route('/create'),
            'edit' => Pages\EditCombinedPeriod::route('/{record}/edit'),
        ];
    }
}
