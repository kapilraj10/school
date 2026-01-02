<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimetableSettingResource\Pages;
use App\Models\TimetableSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimetableSettingResource extends Resource
{
    protected static ?string $model = TimetableSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $modelLabel = 'Setting';

    protected static ?string $navigationGroup = 'Timetable Settings';

    protected static ?int $navigationSort = 0;

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
                            ->visible(fn (Forms\Get $get) => $get('type') === 'json')
                            ->helperText('Enter valid JSON'),

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
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->value),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'gray',
                        'integer' => 'info',
                        'boolean' => 'success',
                        'json' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color('primary'),

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
                            ['key' => 'periods_per_day', 'value' => '8', 'type' => 'integer', 'group' => 'periods', 'description' => 'Number of periods per school day'],
                            ['key' => 'period_duration_minutes', 'value' => '40', 'type' => 'integer', 'group' => 'periods', 'description' => 'Duration of each period in minutes'],
                            ['key' => 'break_after_period', 'value' => '4', 'type' => 'integer', 'group' => 'periods', 'description' => 'Break after this period number'],
                            ['key' => 'break_duration_minutes', 'value' => '20', 'type' => 'integer', 'group' => 'periods', 'description' => 'Duration of break in minutes'],
                            ['key' => 'max_same_subject_per_day', 'value' => '2', 'type' => 'integer', 'group' => 'algorithm', 'description' => 'Maximum times same subject can appear per day'],
                            ['key' => 'respect_teacher_availability', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Check teacher availability when assigning'],
                            ['key' => 'balance_daily_load', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Distribute subjects evenly across days'],
                            ['key' => 'avoid_consecutive_subjects', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Avoid scheduling same subject back-to-back'],
                            ['key' => 'school_days', 'value' => '["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"]', 'type' => 'json', 'group' => 'general', 'description' => 'Days when school is in session'],
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
