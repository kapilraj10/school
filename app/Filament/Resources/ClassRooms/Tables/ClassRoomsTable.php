<?php

namespace App\Filament\Resources\ClassRooms\Tables;

use App\Models\ClassRoom;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
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
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderByRaw('CAST(REPLACE(name, "Class ", "") AS INTEGER) '.$direction);
                    })
                    ->weight('bold')
                    ->icon('heroicon-m-academic-cap'),

                TextColumn::make('section')
                    ->label('Section')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['name', 'section'])
                    ->sortable()
                    ->toggleable(),

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

                TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->sortable()
                    ->suffix(' students')
                    ->alignEnd(),

                TextColumn::make('students_count')
                    ->label('Assigned Students')
                    ->counts('students')
                    ->sortable()
                    ->badge()
                    ->color('primary')
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
                    ->color(fn (string $state): string => match ($state) {
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

                SelectFilter::make('section')
                    ->label('Section')
                    ->options(fn () => ClassRoom::query()
                        ->select('section')
                        ->distinct()
                        ->orderBy('section')
                        ->pluck('section', 'section')
                        ->all())
                    ->multiple()
                    ->native(false),
            ])
            ->actions([
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
