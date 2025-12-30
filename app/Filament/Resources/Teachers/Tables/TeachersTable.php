<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Models\Subject;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Teacher')
                    ->description(fn ($record) => implode(' • ', array_filter([
                        $record->employee_id ? "ID: {$record->employee_id}" : null,
                        $record->email,
                        $record->phone,
                    ])))
                    ->searchable(['name', 'employee_id', 'email', 'phone'])
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                TextColumn::make('subjects')
                    ->label('Subjects')
                    ->formatStateUsing(function ($record) {
                        if (empty($record->subject_ids)) {
                            return 'None';
                        }
                        $subjects = Subject::whereIn('id', $record->subject_ids)
                            ->pluck('name')
                            ->toArray();
                        return implode(', ', $subjects);
                    })
                    ->badge()
                    ->color(Color::Indigo)
                    ->searchable(false),

                TextColumn::make('max_periods_per_day')
                    ->label('Max/Day')
                    ->sortable()
                    ->suffix(' periods')
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('max_periods_per_week')
                    ->label('Max/Week')
                    ->sortable()
                    ->suffix(' periods')
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('timetable_slots_count')
                    ->label('Assigned Slots')
                    ->counts('timetableSlots')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('available_days')
                    ->label('Available Days')
                    ->formatStateUsing(function ($record) {
                        $days = $record->available_days;
                        if (empty($days) || !is_array($days)) {
                            return 'None';
                        }
                        return implode(', ', $days);
                    })
                    ->wrap()
                    ->color(Color::Green)
                    ->toggleable(),

                TextColumn::make('available_periods')
                    ->label('Available Periods')
                    ->formatStateUsing(function ($record) {
                        $periods = $record->available_periods;
                        if (empty($periods) || !is_array($periods)) {
                            return 'None';
                        }
                        return 'P' . implode(', P', $periods);
                    })
                    ->wrap()
                    ->color(Color::Blue)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->native(false),

                SelectFilter::make('subject_ids')
                    ->label('Subject')
                    ->options(fn () => Subject::active()->pluck('name', 'id'))
                    ->query(function ($query, $state) {
                        if (filled($state['value'])) {
                            return $query->whereJsonContains('subject_ids', (int) $state['value']);
                        }
                    })
                    ->native(false),

                SelectFilter::make('availability')
                    ->label('Availability')
                    ->options([
                        'full_week' => 'Full Week (6 days)',
                        'partial' => 'Partial Availability',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'full_week') {
                            return $query->whereNotNull('available_days')
                                ->whereJsonLength('available_days', '>=', 6);
                        } elseif ($state['value'] === 'partial') {
                            return $query->whereNotNull('available_days')
                                ->whereJsonLength('available_days', '<', 6);
                        }
                    })
                    ->native(false),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
