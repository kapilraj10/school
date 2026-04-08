<?php

namespace App\Filament\Widgets;

use App\Models\PageClick;
use Filament\Widgets\Widget;

class MostUsedWidget extends Widget
{
    protected static string $view = 'filament.widgets.most-used-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getMostUsedLinks(): array
    {
        $topClicks = PageClick::getTopClicked(5);

        if ($topClicks->isEmpty()) {
            return [
                [
                    'label' => 'Timetable Designer',
                    'url' => route('filament.admin.pages.timetable-designer'),
                    'icon' => 'heroicon-o-calendar-days',
                    'color' => 'primary',
                    'click_count' => 0,
                ],
                [
                    'label' => 'Teacher Requirements',
                    'url' => route('filament.admin.pages.teacher-requirements'),
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'color' => 'success',
                    'click_count' => 0,
                ],
                [
                    'label' => 'Class Subject Settings',
                    'url' => route('filament.admin.resources.class-subject-settings.index'),
                    'icon' => 'heroicon-o-adjustments-horizontal',
                    'color' => 'warning',
                    'click_count' => 0,
                ],
                [
                    'label' => 'Classes',
                    'url' => route('filament.admin.resources.class-rooms.class-rooms.index'),
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => 'info',
                    'click_count' => 0,
                ],
                [
                    'label' => 'Teachers',
                    'url' => route('filament.admin.resources.teachers.teachers.index'),
                    'icon' => 'heroicon-o-user-group',
                    'color' => 'danger',
                    'click_count' => 0,
                ],
            ];
        }

        $iconMap = [
            // Timetable Settings
            'General Settings' => ['icon' => 'heroicon-o-cog-6-tooth', 'color' => 'gray'],
            'Class Ranges' => ['icon' => 'heroicon-o-queue-list', 'color' => 'info'],
            'Class Subject Settings' => ['icon' => 'heroicon-o-adjustments-horizontal', 'color' => 'warning'],
            'Teacher Requirements' => ['icon' => 'heroicon-o-clipboard-document-check', 'color' => 'success'],
            // Timetable Management
            'Timetable Designer' => ['icon' => 'heroicon-o-calendar-days', 'color' => 'primary'],
            'Generate Timetable' => ['icon' => 'heroicon-o-sparkles', 'color' => 'primary'],
            'View Timetable' => ['icon' => 'heroicon-o-eye', 'color' => 'info'],
            'Teacher Schedule' => ['icon' => 'heroicon-o-calendar-days', 'color' => 'success'],
            'Conflict Checker' => ['icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger'],
            'Print Center' => ['icon' => 'heroicon-o-printer', 'color' => 'primary'],
            'Combined Periods' => ['icon' => 'heroicon-o-squares-2x2', 'color' => 'info'],
            // Academic Management
            'Classes' => ['icon' => 'heroicon-o-academic-cap', 'color' => 'info'],
            'Subjects' => ['icon' => 'heroicon-o-book-open', 'color' => 'info'],
            'Teachers' => ['icon' => 'heroicon-o-user-group', 'color' => 'danger'],
            'Academic Terms' => ['icon' => 'heroicon-o-calendar', 'color' => 'primary'],
            'Holidays' => ['icon' => 'heroicon-o-sun', 'color' => 'warning'],
        ];

        return $topClicks->map(function ($click) use ($iconMap) {
            $iconData = $iconMap[$click->page_name] ?? ['icon' => 'heroicon-o-link', 'color' => 'gray'];

            return [
                'label' => $click->page_name,
                'url' => $click->url,
                'icon' => $iconData['icon'],
                'color' => $iconData['color'],
                'click_count' => $click->click_count,
            ];
        })->toArray();
    }

    protected function getViewData(): array
    {
        return [
            'links' => $this->getMostUsedLinks(),
        ];
    }
}
