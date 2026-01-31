<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimetableSettingResource\Pages;
use App\Models\TimetableSetting;
use Filament\Forms;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimetableSettingResource extends Resource
{
    protected static ?string $model = TimetableSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $recordTitleAttribute = 'key';

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $modelLabel = 'Setting';

    protected static ?string $navigationGroup = 'Timetable Settings';

    protected static ?int $navigationSort = 0;

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
                Tables\Actions\Action::make('seed_defaults')
                    ->label('Seed Defaults')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Seed Default Settings')
                    ->modalDescription('This will create default settings if they do not exist. Existing settings will not be overwritten.')
                    ->action(function () {
                        $defaults = [
                            ['key' => 'school_days', 'value' => '["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"]', 'type' => 'json', 'group' => 'general', 'description' => 'Days when school is in session'],
                            ['key' => 'periods_per_day', 'value' => '8', 'type' => 'integer', 'group' => 'periods', 'description' => 'Number of periods per school day'],
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
