<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassSubjectSettingResource\Pages;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClassSubjectSettingResource extends Resource
{
    protected static ?string $model = ClassSubjectSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Class Subject Settings';

    protected static ?string $modelLabel = 'Class Subject Setting';

    protected static ?string $pluralModelLabel = 'Class Subject Settings';

    protected static ?string $navigationGroup = 'Timetable Settings';

    protected static ?int $navigationSort = 2;

    public static function getGlobalSearchResultTitle($record): string
    {
        return "{$record->classRoom->full_name} - {$record->subject->name}";
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['classRoom.name', 'classRoom.section', 'subject.name', 'subject.code'];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['classRoom', 'subject']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Class & Subject')
                    ->description('Select the class and subject combination')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('class_room_id')
                                    ->label('Class')
                                    ->options(function () {
                                        return ClassRoom::active()
                                            ->get()
                                            ->sortBy(function ($class) {
                                                return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
                                            })
                                            ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Select::make('subject_id')
                                    ->label('Subject')
                                    ->options(Subject::active()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->native(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Period Configuration')
                    ->description('Configure periods for this subject in this class')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('min_periods_per_week')
                                    ->label('Minimum Periods')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->suffix('/week')
                                    ->helperText('Minimum periods that must be assigned'),

                                Forms\Components\TextInput::make('weekly_periods')
                                    ->label('Target Periods')
                                    ->numeric()
                                    ->required()
                                    ->default(4)
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->suffix('/week')
                                    ->helperText('Target number of periods per week'),

                                Forms\Components\TextInput::make('max_periods_per_week')
                                    ->label('Maximum Periods')
                                    ->numeric()
                                    ->required()
                                    ->default(6)
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->suffix('/week')
                                    ->helperText('Maximum periods allowed per week'),
                            ]),
                    ]),

                Forms\Components\Section::make('Additional Settings')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('single_combined')
                                    ->label('Period Type')
                                    ->options([
                                        'single' => 'Single (One Class)',
                                        'combined' => 'Combined (Multiple Classes)',
                                    ])
                                    ->required()
                                    ->default('single')
                                    ->native(false)
                                    ->helperText('Whether periods are for single or combined classes'),

                                Forms\Components\Select::make('type')
                                    ->label('Subject Type')
                                    ->options([
                                        'core' => 'Core',
                                        'co-curricular' => 'Co-Curricular',
                                    ])
                                    ->default('core')
                                    ->native(false)
                                    ->helperText('Core or Co-Curricular subject'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Enable/disable this subject for timetable'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classRoom.full_name')
                    ->label('Class')
                    ->sortable(['class_room_id'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('min_periods_per_week')
                    ->label('Min/Week')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('weekly_periods')
                    ->label('Target/Week')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_periods_per_week')
                    ->label('Max/Week')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subject.type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'core' => 'Core',
                        'co_curricular' => 'Co-Curricular',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'core' => 'success',
                        'co_curricular' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('single_combined')
                    ->label('Single or Combined')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'success',
                        'combined' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('class_room_id')
            ->groups([
                Tables\Grouping\Group::make('classRoom.name')
                    ->label('Class')
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->options(function () {
                        return ClassRoom::active()
                            ->get()
                            ->sortBy(function ($class) {
                                return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
                            })
                            ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                            ->toArray();
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::active()->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('single_combined')
                    ->label('Type')
                    ->options([
                        'single' => 'Single',
                        'combined' => 'Combined',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_all')
                    ->label('Sync All Classes')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Subject Settings')
                    ->modalDescription('This will sync subject settings for all classes based on their class range. Existing custom settings will be preserved.')
                    ->action(function () {
                        $classes = ClassRoom::active()->get();
                        foreach ($classes as $class) {
                            ClassSubjectSetting::syncSubjectsForClass($class);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Sync Complete')
                            ->body('Subject settings have been synced for all classes.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('copy_from_class')
                    ->label('Copy from Another Class')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('source_class_id')
                            ->label('Source Class (Copy From)')
                            ->options(function () {
                                return ClassRoom::active()
                                    ->get()
                                    ->sortBy(function ($class) {
                                        return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
                                    })
                                    ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->helperText('Select the class to copy subject settings from'),

                        Forms\Components\Select::make('target_class_id')
                            ->label('Target Class (Copy To)')
                            ->options(function () {
                                return ClassRoom::active()
                                    ->get()
                                    ->sortBy(function ($class) {
                                        return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
                                    })
                                    ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->helperText('Select the class to copy subject settings to'),

                        Forms\Components\Select::make('conflict_resolution')
                            ->label('Conflict Resolution')
                            ->options([
                                'skip' => 'Skip - Keep existing target settings',
                                'update' => 'Update - Merge source into existing',
                                'replace' => 'Replace - Delete target and copy all',
                            ])
                            ->required()
                            ->default('skip')
                            ->native(false)
                            ->helperText('How to handle subjects that already exist in target class'),
                    ])
                    ->modalHeading('Copy Subject Settings Between Classes')
                    ->modalDescription('Copy all subject settings from one class to another.')
                    ->modalSubmitActionLabel('Copy Settings')
                    ->action(function (array $data) {
                        $sourceClassId = $data['source_class_id'];
                        $targetClassId = $data['target_class_id'];
                        $conflictResolution = $data['conflict_resolution'];

                        // Validate source and target are different
                        if ($sourceClassId === $targetClassId) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Source and target classes must be different.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $copiedCount = 0;

                        // Use database transaction for data integrity
                        \DB::transaction(function () use ($sourceClassId, $targetClassId, $conflictResolution, &$copiedCount) {
                            // Get all source class subject settings
                            $sourceSettings = ClassSubjectSetting::where('class_room_id', $sourceClassId)
                                ->with('subject')
                                ->get();

                            if ($sourceSettings->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Settings Found')
                                    ->body('The source class has no subject settings to copy.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            // Get existing target class settings
                            $existingTargetSubjects = ClassSubjectSetting::where('class_room_id', $targetClassId)
                                ->pluck('subject_id')
                                ->toArray();

                            // Handle conflict resolution
                            if ($conflictResolution === 'replace') {
                                // Delete all existing target settings
                                ClassSubjectSetting::where('class_room_id', $targetClassId)->delete();
                                $existingTargetSubjects = [];
                            }

                            // Copy each setting from source to target
                            foreach ($sourceSettings as $sourceSetting) {
                                $subjectId = $sourceSetting->subject_id;
                                $exists = in_array($subjectId, $existingTargetSubjects);

                                if ($conflictResolution === 'skip' && $exists) {
                                    // Skip if subject already exists in target
                                    continue;
                                }

                                // Prepare data to copy (exclude id, class_room_id, timestamps)
                                $copyData = [
                                    'class_room_id' => $targetClassId,
                                    'subject_id' => $sourceSetting->subject_id,
                                    'min_periods_per_week' => $sourceSetting->min_periods_per_week,
                                    'weekly_periods' => $sourceSetting->weekly_periods,
                                    'max_periods_per_week' => $sourceSetting->max_periods_per_week,
                                    'single_combined' => $sourceSetting->single_combined,
                                    'type' => $sourceSetting->type,
                                    'priority' => $sourceSetting->priority,
                                    'is_active' => $sourceSetting->is_active,
                                ];

                                if ($conflictResolution === 'update' && $exists) {
                                    // Update existing record
                                    ClassSubjectSetting::where('class_room_id', $targetClassId)
                                        ->where('subject_id', $subjectId)
                                        ->update($copyData);
                                } else {
                                    // Create new record
                                    ClassSubjectSetting::create($copyData);
                                }

                                $copiedCount++;
                            }
                        });

                        // Send success notification
                        $sourceClass = ClassRoom::find($sourceClassId);
                        $targetClass = ClassRoom::find($targetClassId);

                        \Filament\Notifications\Notification::make()
                            ->title('Copy Complete')
                            ->body("Successfully copied {$copiedCount} subject setting(s) from {$sourceClass->full_name} to {$targetClass->full_name}.")
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListClassSubjectSettings::route('/'),
            'create' => Pages\CreateClassSubjectSetting::route('/create'),
            'edit' => Pages\EditClassSubjectSetting::route('/{record}/edit'),
        ];
    }
}
