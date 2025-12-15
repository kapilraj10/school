<?php

namespace App\Filament\Resources\ClassRooms\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassRoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Class')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-academic-cap'),

                TextColumn::make('section')
                    ->label('Section')
                    ->badge()
                    ->color('blue')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['name', 'section'])
                    ->sortable()
                    ->toggleable()
                    ->description(fn ($record) => $record->level_display),

                TextColumn::make('level')
                    ->label('Level')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'basic_1_3' => 'Basic (1-3)',
                        'basic_4_8' => 'Basic (4-8)',
                        'secondary_9_10' => 'Secondary (9-10)',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'basic_1_3' => 'success',
                        'basic_4_8' => 'warning',
                        'secondary_9_10' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('weekly_periods')
                    ->label('Weekly Periods')
                    ->numeric()
                    ->sortable()
                    ->suffix(' periods')
                    ->alignEnd(),

                TextColumn::make('total_subjects')
                    ->label('Total Subjects')
                    ->numeric()
                    ->sortable()
                    ->suffix(' subjects')
                    ->alignEnd(),

                TextColumn::make('timetable_slots_count')
                    ->label('Timetable Slots')
                    ->counts('timetableSlots')
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),

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
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        'basic_1_3' => 'Basic (1-3)',
                        'basic_4_8' => 'Basic (4-8)',
                        'secondary_9_10' => 'Secondary (9-10)',
                    ])
                    ->multiple()
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->native(false),

                SelectFilter::make('section')
                    ->label('Section')
                    ->options([
                        'A' => 'Section A',
                        'B' => 'Section B',
                        'C' => 'Section C',
                        'D' => 'Section D',
                    ])
                    ->multiple()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_timetable')
                    ->label('View Timetable')
                    ->icon('heroicon-m-calendar')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.pages.timetable-viewer', [
                        'class_id' => $record->id,
                    ]))
                    ->openUrlInNewTab(),
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
