<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\ClassRoom;
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

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Subjects';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'classRoom.name', 'classRoom.section'];
    }

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
                        TextInput::make('code')
                            ->label('Subject Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('e.g., MATH-1A')
                            ->helperText('Unique code for this subject'),
                        Select::make('class_room_id')
                            ->label('Class')
                            ->relationship('classRoom', 'name')
                            ->getOptionLabelFromRecordUsing(fn (ClassRoom $record) => "{$record->name} - {$record->section}")
                            ->searchable(['name', 'section'])
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText('Select the class for this subject'),
                        Select::make('type')
                            ->label('Subject Type')
                            ->options([
                                'core' => 'Core Subject',
                                'elective' => 'Elective',
                                'co_curricular' => 'Co-Curricular',
                            ])
                            ->default('core')
                            ->required()
                            ->native(false),
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'core' => 'success',
                        'elective' => 'info',
                        'co_curricular' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'core' => 'Core',
                        'elective' => 'Elective',
                        'co_curricular' => 'Co-Curricular',
                        default => $state,
                    }),
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
                Tables\Grouping\Group::make('classRoom.name')
                    ->label('Class')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (Subject $record) => "{$record->classRoom->name} - {$record->classRoom->section}"),
            ])
            ->defaultGroup('classRoom.name')
            ->filters([
                Tables\Filters\SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->relationship('classRoom', 'name')
                    ->getOptionLabelFromRecordUsing(fn (ClassRoom $record) => "{$record->name} - {$record->section}")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'core' => 'Core',
                        'elective' => 'Elective',
                        'co_curricular' => 'Co-Curricular',
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
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-clipboard-document')
                    ->form([
                        Select::make('target_class_ids')
                            ->label('Copy to Classes')
                            ->multiple()
                            ->options(fn (Subject $record) => ClassRoom::where('id', '!=', $record->class_room_id)
                                ->get()
                                ->mapWithKeys(fn ($class) => [
                                    $class->id => "{$class->name} - {$class->section}",
                                ])
                            )
                            ->searchable()
                            ->required()
                            ->helperText('Select classes to copy this subject to'),
                    ])
                    ->action(function (Subject $record, array $data) {
                        foreach ($data['target_class_ids'] as $classId) {
                            $classRoom = ClassRoom::find($classId);
                            $classNumber = preg_replace('/[^0-9]/', '', $classRoom->name);
                            $section = $classRoom->section;
                            $baseCode = preg_replace('/-[\d-]+[A-Z]?$/', '', $record->code);
                            $newCode = "{$baseCode}-{$classNumber}{$section}";

                            $counter = 1;
                            $originalCode = $newCode;
                            while (Subject::where('code', $newCode)->exists()) {
                                $newCode = "{$originalCode}-{$counter}";
                                $counter++;
                            }

                            Subject::create([
                                'name' => $record->name,
                                'code' => $newCode,
                                'class_room_id' => $classId,
                                'type' => $record->type,
                                'weekly_periods' => $record->weekly_periods,
                                'min_periods_per_week' => $record->min_periods_per_week,
                                'max_periods_per_week' => $record->max_periods_per_week,
                                'level' => $record->level,
                                'single_combined' => $record->single_combined,
                                'status' => $record->status,
                            ]);
                        }
                    })
                    ->successNotificationTitle('Subject copied successfully')
                    ->color('success'),
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
            'index' => Pages\ManageSubjectsByClass::route('/'),
            'list' => Pages\ListSubjects::route('/list'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
