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

                                Forms\Components\TextInput::make('priority')
                                    ->label('Priority')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->helperText('1-10, higher = more important'),

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

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 8 => 'danger',
                        $state >= 5 => 'warning',
                        default => 'gray',
                    }),

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
