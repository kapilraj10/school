<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
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
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->badge()
                    ->color(Color::Sky),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),

                TextColumn::make('subjects')
                    ->label('Subjects')
                    ->formatStateUsing(function ($record) {
                        if (empty($record->subject_ids)) {
                            return [];
                        }
                        return Subject::whereIn('id', $record->subject_ids)
                            ->pluck('name')
                            ->toArray();
                    })
                    ->badge()
                    ->wrap()
                    ->limit(3)
                    ->color(Color::Indigo),

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

                TextColumn::make('unavailable_periods')
                    ->label('Unavailable Periods')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'None';
                        }
                        return count($state) . ' slots';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        'available' => 'Fully Available',
                        'restricted' => 'Has Restrictions',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'available') {
                            return $query->whereNull('unavailable_periods')
                                ->orWhereJsonLength('unavailable_periods', 0);
                        } elseif ($state['value'] === 'restricted') {
                            return $query->whereNotNull('unavailable_periods')
                                ->whereJsonLength('unavailable_periods', '>', 0);
                        }
                    })
                    ->native(false),
            ])
            // ->recordActions([
            //     ViewAction::make(),
            //     EditAction::make(),
            //     DeleteAction::make(),
            //     Action::make('view_timetable')
            //         ->label('View Timetable')
            //         ->icon('heroicon-m-calendar')
            //         ->color(Color::Sky)
            //         ->url(fn ($record) => route('filament.admin.resources.timetable-slots.index', [
            //             'tableFilters' => [
            //                 'teacher_id' => ['value' => $record->id],
            //             ],
            //         ]))
            //         ->openUrlInNewTab(),
            // ])
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
