<?php

namespace App\Filament\Widgets;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();
        $scheduledPeriods = $currentTerm 
            ? TimetableSlot::where('academic_term_id', $currentTerm->id)->count() 
            : 0;

        return [
            Stat::make('Active Classes', ClassRoom::active()->count())
                ->description('Total active classes')
                ->icon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Total Teachers', Teacher::active()->count())
                ->description('Active teaching staff')
                ->icon('heroicon-o-user-group')
                ->color('warning'),

            Stat::make('Subjects', Subject::active()->count())
                ->description('Active subjects')
                ->icon('heroicon-o-book-open')
                ->color('info'),

            Stat::make('Scheduled Periods', $scheduledPeriods)
                ->description('Current term: ' . ($currentTerm->name ?? 'N/A'))
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
        ];
    }
}
