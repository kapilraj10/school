<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CombinedPeriodResource\Pages;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CombinedPeriodResource extends Resource
{
    protected static ?string $model = CombinedPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Combined Periods';

    protected static ?string $navigationGroup = 'Timetable Management';

    protected static ?int $navigationSort = 6;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'subject.name', 'teacher.name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                        ->options(ClassRoom::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->full_name]))
                        ->required()
                        ->columns(4)
                        ->helperText('Select all classes that will attend together. Only same-grade classes can be combined (e.g., Class 1A + Class 1B is allowed, but Class 1A + Class 2A is not).')
                        ->rule('array')
                        ->rule(function () {
                            return function ($attribute, $value, $fail) {
                                if (empty($value) || count($value) < 2) {
                                    return;
                                }

                                $classes = ClassRoom::whereIn('id', $value)->get();
                                $grades = $classes->map(function ($class) {
                                    preg_match('/(\d+)/', $class->name, $matches);

                                    return isset($matches[1]) ? (int) $matches[1] : null;
                                })->filter()->unique();

                                if ($grades->count() > 1) {
                                    $fail('Only classes from the same grade can be combined. For example, Class 1A and Class 1B can be combined, but Class 1A and Class 2A cannot.');
                                }
                            };
                        }),
                    Grid::make(3)->schema([
                        Select::make('day')
                            ->label('Day')
                            ->options(TimetableSlot::getDays())
                            ->required()
                            ->native(false),
                        Select::make('period')
                            ->label('Period')
                            ->options(TimetableSlot::getPeriods())
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
                    ->formatStateUsing(fn ($state) => TimetableSlot::getDays()[$state] ?? 'N/A')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('period')
                    ->formatStateUsing(fn ($state) => "Period {$state}")
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('class_room_ids')
                    ->label('Classes')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'None';
                        }
                        $count = count($state);

                        return "{$count} ".($count === 1 ? 'class' : 'classes');
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
            'index' => Pages\ListCombinedPeriods::route('/'),
            'create' => Pages\CreateCombinedPeriod::route('/create'),
            'edit' => Pages\EditCombinedPeriod::route('/{record}/edit'),
        ];
    }
}
