<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\TimetableSetting;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacher extends ViewRecord
{
    protected static string $resource = TeacherResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name'),

                                TextEntry::make('employee_id')
                                    ->label('Employee ID'),

                                TextEntry::make('email')
                                    ->label('Email Address'),

                                TextEntry::make('phone')
                                    ->label('Phone Number'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                            ]),
                    ]),

                Section::make('Subject Assignment')
                    ->schema([
                        TextEntry::make('subjects')
                            ->label('Subjects Can Teach')
                            ->getStateUsing(function ($record) {
                                if (! $record->subject_ids || empty($record->subject_ids)) {
                                    return 'None';
                                }

                                return \App\Models\Subject::query()
                                    ->whereIn('id', $record->subject_ids)
                                    ->pluck('name')
                                    ->join(', ');
                            }),
                    ]),

                Section::make('Class Assignment')
                    ->schema([
                        TextEntry::make('classes')
                            ->label('Assigned Classes')
                            ->getStateUsing(function ($record) {
                                if (! $record->class_room_ids || empty($record->class_room_ids)) {
                                    return 'All Classes';
                                }

                                return \App\Models\ClassRoom::query()
                                    ->whereIn('id', $record->class_room_ids)
                                    ->get()
                                    ->pluck('full_name')
                                    ->join(', ');
                            }),
                    ]),

                Section::make('Teaching Capacity')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('max_periods_per_day')
                                    ->label('Maximum Periods per Day')
                                    ->suffix(' periods'),

                                TextEntry::make('max_periods_per_week')
                                    ->label('Maximum Periods per Week')
                                    ->suffix(' periods'),
                            ]),
                    ]),

                Section::make('Teacher Availability')
                    ->description('Days and periods when this teacher is available to teach')
                    ->schema([
                        ViewEntry::make('availability_grid')
                            ->label('')
                            ->view('filament.resources.teachers.availability-grid')
                            ->viewData(function ($record) {
                                $matrix = $record->availability_matrix ?? [];

                                // Extract available days from the matrix
                                $availableDays = array_keys($matrix);

                                // Extract available periods: any period number where at least one day has it set to true
                                $availablePeriods = [];
                                foreach ($matrix as $day => $periods) {
                                    if (is_array($periods)) {
                                        foreach ($periods as $period => $isAvailable) {
                                            if ($isAvailable && ! in_array($period, $availablePeriods)) {
                                                $availablePeriods[] = $period;
                                            }
                                        }
                                    }
                                }
                                sort($availablePeriods);

                                return [
                                    'availableDays' => $availableDays,
                                    'availablePeriods' => $availablePeriods,
                                    'schoolDays' => TimetableSetting::get('school_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                                    'periodsPerDay' => TimetableSetting::get('periods_per_day', 8),
                                ];
                            }),
                    ]),
            ]);
    }
}
