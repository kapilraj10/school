<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\TimetableSettingResource\Pages;
use App\Models\TimetableSetting;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TimetableSettingResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = TimetableSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $recordTitleAttribute = 'key';

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $modelLabel = 'Setting';

    protected static ?string $navigationGroup = 'Timetable Settings';

    protected static ?int $navigationSort = 0;

    protected static function permissionPrefix(): string
    {
        return 'timetable_setting';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['key', 'group', 'description'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Details')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Setting Key')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record !== null)
                            ->helperText('Unique identifier for this setting'),

                        Forms\Components\Select::make('type')
                            ->label('Value Type')
                            ->options([
                                'string' => 'Text',
                                'integer' => 'Integer',
                                'boolean' => 'Yes/No',
                                'json' => 'JSON/Array',
                            ])
                            ->required()
                            ->default('string')
                            ->native(false)
                            ->live(),

                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['string', 'integer', null])),

                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'boolean')
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),

                        Forms\Components\Textarea::make('value')
                            ->label('Value (JSON)')
                            ->rows(5)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'json' && $get('key') !== 'school_days')
                            ->helperText('Enter valid JSON'),

                        TagsInput::make('value')
                            ->label('School Days')
                            ->placeholder('Add day')
                            ->visible(fn (Forms\Get $get) => $get('key') === 'school_days')
                            ->helperText('Enter the days when school is in session')
                            ->dehydrateStateUsing(fn ($state) => json_encode(array_values($state ?? [])))
                            ->afterStateHydrated(function (TagsInput $component, $state) {
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    $component->state($decoded ?? []);
                                }
                            }),

                        Forms\Components\Select::make('group')
                            ->label('Group')
                            ->options([
                                'general' => 'General',
                                'periods' => 'Periods',
                                'algorithm' => 'Algorithm',
                                'classes' => 'Classes',
                            ])
                            ->required()
                            ->default('general')
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(500),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(function ($record) {
                        if ($record->type === 'boolean') {
                            return $record->value === '1' ? 'Yes' : 'No';
                        }
                        if ($record->type === 'json') {
                            $decoded = json_decode($record->value, true);
                            if (is_array($decoded)) {
                                return implode(', ', $decoded);
                            }
                        }

                        return $record->value;
                    })
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->value),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type'),

                Tables\Columns\TextColumn::make('group')
                    ->label('Group'),

                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Group')
                    ->collapsible(),
            ])
            ->defaultGroup('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'periods' => 'Periods',
                        'algorithm' => 'Algorithm',
                        'classes' => 'Classes',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'string' => 'Text',
                        'integer' => 'Integer',
                        'boolean' => 'Yes/No',
                        'json' => 'JSON',
                    ]),
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
                Tables\Actions\Action::make('configure_time_slots')
                    ->label('Time Slot Management')
                    ->icon('heroicon-o-clock')
                    ->color('primary')
                    ->visible(fn () => (bool) Auth::user()?->can('timetable_setting.edit'))
                    ->form([
                        Forms\Components\Section::make('Working Days')
                            ->description('Define school working days (e.g., Sunday to Friday)')
                            ->schema([
                                CheckboxList::make('school_days')
                                    ->options([
                                        'Sunday' => 'Sunday',
                                        'Monday' => 'Monday',
                                        'Tuesday' => 'Tuesday',
                                        'Wednesday' => 'Wednesday',
                                        'Thursday' => 'Thursday',
                                        'Friday' => 'Friday',
                                        'Saturday' => 'Saturday',
                                    ])
                                    ->default(fn () => TimetableSetting::get('school_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']))
                                    ->required()
                                    ->columns(4),
                            ]),

                        Forms\Components\Section::make('Periods')
                            ->description('Create period slots and duration')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('periods_per_day')
                                            ->label('Periods Per Day')
                                            ->numeric()
                                            ->required()
                                            ->default(fn () => (int) TimetableSetting::get('periods_per_day', 8))
                                            ->minValue(1)
                                            ->maxValue(12),

                                        Forms\Components\TextInput::make('period_duration_minutes')
                                            ->label('Period Duration (Minutes)')
                                            ->numeric()
                                            ->required()
                                            ->default(fn () => (int) TimetableSetting::get('period_duration_minutes', 45))
                                            ->minValue(20)
                                            ->maxValue(90),

                                        Forms\Components\TimePicker::make('school_start_time')
                                            ->label('School Start Time')
                                            ->seconds(false)
                                            ->native(false)
                                            ->required()
                                            ->default(fn () => (string) TimetableSetting::get('school_start_time', '09:00')),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Breaks')
                            ->description('Set short break and lunch break timing')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('short_break_after_period')
                                            ->label('Short Break After Period')
                                            ->options(function () {
                                                $periods = (int) TimetableSetting::get('periods_per_day', 8);

                                                return collect(range(1, $periods))
                                                    ->mapWithKeys(fn (int $period) => [(string) $period => "After Period {$period}"])
                                                    ->all();
                                            })
                                            ->default(fn () => (string) TimetableSetting::get('short_break_after_period', '3'))
                                            ->required()
                                            ->native(false),

                                        Forms\Components\TextInput::make('short_break_duration_minutes')
                                            ->label('Short Break Duration (Minutes)')
                                            ->numeric()
                                            ->required()
                                            ->default(fn () => (int) TimetableSetting::get('short_break_duration_minutes', 15))
                                            ->minValue(5)
                                            ->maxValue(45),

                                        Forms\Components\Select::make('lunch_break_after_period')
                                            ->label('Lunch Break After Period')
                                            ->options(function () {
                                                $periods = (int) TimetableSetting::get('periods_per_day', 8);

                                                return collect(range(1, $periods))
                                                    ->mapWithKeys(fn (int $period) => [(string) $period => "After Period {$period}"])
                                                    ->all();
                                            })
                                            ->default(fn () => (string) TimetableSetting::get('lunch_break_after_period', '5'))
                                            ->required()
                                            ->native(false),

                                        Forms\Components\TextInput::make('lunch_break_duration_minutes')
                                            ->label('Lunch Break Duration (Minutes)')
                                            ->numeric()
                                            ->required()
                                            ->default(fn () => (int) TimetableSetting::get('lunch_break_duration_minutes', 30))
                                            ->minValue(10)
                                            ->maxValue(90),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $periodsPerDay = (int) ($data['periods_per_day'] ?? 8);
                        $shortBreakAfter = (int) ($data['short_break_after_period'] ?? 0);
                        $lunchBreakAfter = (int) ($data['lunch_break_after_period'] ?? 0);

                        if ($shortBreakAfter === $lunchBreakAfter) {
                            throw ValidationException::withMessages([
                                'lunch_break_after_period' => 'Lunch break period must be different from short break period.',
                            ]);
                        }

                        if ($shortBreakAfter > $periodsPerDay || $lunchBreakAfter > $periodsPerDay) {
                            throw ValidationException::withMessages([
                                'periods_per_day' => 'Break periods must be within total periods per day.',
                            ]);
                        }

                        $schoolDays = array_values($data['school_days'] ?? []);

                        TimetableSetting::set('school_days', $schoolDays, 'json', 'general', 'Days when school is in session');
                        TimetableSetting::set('periods_per_day', $periodsPerDay, 'integer', 'periods', 'Number of periods per school day');
                        TimetableSetting::set('period_duration_minutes', (int) $data['period_duration_minutes'], 'integer', 'periods', 'Duration of each period in minutes');
                        TimetableSetting::set('school_start_time', (string) $data['school_start_time'], 'string', 'periods', 'Start time of school day');
                        TimetableSetting::set('short_break_after_period', $shortBreakAfter, 'integer', 'periods', 'Period number after which short break occurs');
                        TimetableSetting::set('short_break_duration_minutes', (int) $data['short_break_duration_minutes'], 'integer', 'periods', 'Duration of short break in minutes');
                        TimetableSetting::set('lunch_break_after_period', $lunchBreakAfter, 'integer', 'periods', 'Period number after which lunch break occurs');
                        TimetableSetting::set('lunch_break_duration_minutes', (int) $data['lunch_break_duration_minutes'], 'integer', 'periods', 'Duration of lunch break in minutes');

                        \Filament\Notifications\Notification::make()
                            ->title('Time slot settings saved')
                            ->body('Working days, periods, and break timings have been updated.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('seed_defaults')
                    ->label('Seed Defaults')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => (bool) Auth::user()?->can('timetable_setting.edit'))
                    ->requiresConfirmation()
                    ->modalHeading('Seed Default Settings')
                    ->modalDescription('This will create default settings if they do not exist. Existing settings will not be overwritten.')
                    ->action(function () {
                        $defaults = [
                            ['key' => 'school_days', 'value' => '["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"]', 'type' => 'json', 'group' => 'general', 'description' => 'Days when school is in session'],
                            ['key' => 'periods_per_day', 'value' => '8', 'type' => 'integer', 'group' => 'periods', 'description' => 'Number of periods per school day'],
                            ['key' => 'period_duration_minutes', 'value' => '45', 'type' => 'integer', 'group' => 'periods', 'description' => 'Duration of each period in minutes'],
                            ['key' => 'school_start_time', 'value' => '09:00', 'type' => 'string', 'group' => 'periods', 'description' => 'Start time of school day'],
                            ['key' => 'short_break_after_period', 'value' => '3', 'type' => 'integer', 'group' => 'periods', 'description' => 'Period after which short break occurs'],
                            ['key' => 'short_break_duration_minutes', 'value' => '15', 'type' => 'integer', 'group' => 'periods', 'description' => 'Duration of short break in minutes'],
                            ['key' => 'lunch_break_after_period', 'value' => '5', 'type' => 'integer', 'group' => 'periods', 'description' => 'Period after which lunch break occurs'],
                            ['key' => 'lunch_break_duration_minutes', 'value' => '30', 'type' => 'integer', 'group' => 'periods', 'description' => 'Duration of lunch break in minutes'],
                            ['key' => 'max_same_subject_per_day', 'value' => '2', 'type' => 'integer', 'group' => 'algorithm', 'description' => 'Maximum times same subject can appear per day'],
                            ['key' => 'respect_teacher_availability', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Check teacher availability when assigning'],
                            ['key' => 'balance_daily_load', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Distribute subjects evenly across days'],
                            ['key' => 'avoid_consecutive_subjects', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Avoid scheduling same subject back-to-back'],
                        ];

                        foreach ($defaults as $setting) {
                            TimetableSetting::firstOrCreate(
                                ['key' => $setting['key']],
                                $setting
                            );
                        }

                        // Seed new algorithm settings
                        $algorithmDefaults = [
                            ['key' => 'max_teacher_periods_per_day', 'value' => '6', 'type' => 'integer', 'group' => 'algorithm', 'description' => 'Maximum periods a teacher can teach per day'],
                            ['key' => 'heavy_subjects', 'value' => '["Maths","Science","English","Nepali","Social"]', 'type' => 'json', 'group' => 'algorithm', 'description' => 'Mentally demanding subjects to avoid scheduling consecutively'],
                            ['key' => 'core_subjects', 'value' => '["English","Maths","Science","Nepali"]', 'type' => 'json', 'group' => 'algorithm', 'description' => 'Core subjects placed in early periods for positional consistency'],
                            ['key' => 'preferred_eca_periods', 'value' => '[4,5,6,7,8]', 'type' => 'json', 'group' => 'algorithm', 'description' => 'Preferred period positions for ECA subjects'],
                            ['key' => 'max_eca_periods_per_day', 'value' => '2', 'type' => 'integer', 'group' => 'algorithm', 'description' => 'Maximum ECA periods per day per type'],
                        ];

                        foreach ($algorithmDefaults as $setting) {
                            TimetableSetting::firstOrCreate(
                                ['key' => $setting['key']],
                                $setting
                            );
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Defaults Seeded')
                            ->body('Default settings have been created.')
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
            'index' => Pages\ListTimetableSettings::route('/'),
            'create' => Pages\CreateTimetableSetting::route('/create'),
            'edit' => Pages\EditTimetableSetting::route('/{record}/edit'),
        ];
    }
}
