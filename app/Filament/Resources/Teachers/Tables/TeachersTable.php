<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Models\ClassRoom;
use App\Models\Subject;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Teacher')
                    ->description(function ($record) {
                        $lines = array_filter([
                            $record->employee_id ? "ID: {$record->employee_id}" : null,
                            $record->email,
                            $record->phone,
                        ]);

                        return new HtmlString(implode('<br>', $lines));
                    })
                    ->searchable(['name', 'employee_id', 'email', 'phone'])
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                TextColumn::make('subject_ids')
                    ->label('Subjects')
                    ->state(function ($record) {
                        if (empty($record->subject_ids)) {
                            return ['None'];
                        }

                        $subjectLabels = Subject::query()
                            ->whereIn('id', array_map('intval', (array) $record->subject_ids))
                            ->with('classRoom:id,name')
                            ->get(['id', 'name', 'class_room_id'])
                            ->mapWithKeys(function (Subject $subject): array {
                                $className = $subject->classRoom?->name;
                                $label = $subject->name;

                                if ($className) {
                                    $label .= " - {$className}";
                                }

                                return [$label => $label];
                            })
                            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
                            ->values()
                            ->all();

                        return $subjectLabels === [] ? ['None'] : $subjectLabels;
                    })
                    ->badge()
                    ->color(Color::Indigo)
                    ->searchable(false)
                    ->wrap(),

                TextColumn::make('assigned_classes')
                    ->label('Assigned Classes')
                    ->state(function ($record) {
                        if (empty($record->class_room_ids)) {
                            return 'All Classes';
                        }

                        return $record->classRooms()
                            ->pluck('full_name') // Accessor logic from Model
                            ->join(', ');
                    })
                    ->badge()
                    ->color(Color::Amber)
                    ->searchable(false)
                    ->wrap(),

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

                SelectFilter::make('subject_ids')
                    ->label('Subject')
                    ->options(fn () => Subject::active()->pluck('name', 'id'))
                    ->query(function ($query, $state) {
                        if (filled($state['value'])) {
                            return $query->whereJsonContains('subject_ids', (int) $state['value']);
                        }
                    })
                    ->native(false),

                SelectFilter::make('class_room_ids')
                    ->label('Assigned Class')
                    ->options(fn () => ClassRoom::active()
                        ->get()
                        ->sortBy(fn ($class) => $class->name.$class->section, SORT_NATURAL | SORT_FLAG_CASE)
                        ->mapWithKeys(fn ($class) => [$class->id => $class->full_name]))
                    ->query(function ($query, $state) {
                        if (filled($state['value'])) {
                            return $query->whereJsonContains('class_room_ids', (int) $state['value']);
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
                            // Teachers available all 6 days
                            return $query->whereNotNull('availability_matrix')
                                ->whereRaw("json_extract(availability_matrix, '$.Mon') IS NOT NULL")
                                ->whereRaw("json_extract(availability_matrix, '$.Tue') IS NOT NULL")
                                ->whereRaw("json_extract(availability_matrix, '$.Wed') IS NOT NULL")
                                ->whereRaw("json_extract(availability_matrix, '$.Thu') IS NOT NULL")
                                ->whereRaw("json_extract(availability_matrix, '$.Fri') IS NOT NULL")
                                ->whereRaw("json_extract(availability_matrix, '$.Sat') IS NOT NULL");
                        } elseif ($state['value'] === 'partial') {
                            // Teachers available less than 6 days
                            return $query->whereNotNull('availability_matrix')
                                ->where(function ($q) {
                                    $q->whereRaw("json_extract(availability_matrix, '$.Mon') IS NULL")
                                        ->orWhereRaw("json_extract(availability_matrix, '$.Tue') IS NULL")
                                        ->orWhereRaw("json_extract(availability_matrix, '$.Wed') IS NULL")
                                        ->orWhereRaw("json_extract(availability_matrix, '$.Thu') IS NULL")
                                        ->orWhereRaw("json_extract(availability_matrix, '$.Fri') IS NULL")
                                        ->orWhereRaw("json_extract(availability_matrix, '$.Sat') IS NULL");
                                });
                        }
                    })
                    ->native(false),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
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
