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
                    'label' => 'Class Timetable Designer',
                    'url' => route('filament.admin.pages.timetable-designer'),
                    'icon' => 'heroicon-o-calendar-days',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Teacher Requirements',
                    'url' => route('filament.admin.pages.teacher-requirements'),
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'color' => 'success',
                ],
                [
                    'label' => 'Class Subject Settings',
                    'url' => route('filament.admin.resources.class-subject-settings.index'),
                    'icon' => 'heroicon-o-adjustments-horizontal',
                    'color' => 'warning',
                ],
                [
                    'label' => 'Class Rooms',
                    'url' => route('filament.admin.resources.class-rooms.class-rooms.index'),
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => 'info',
                ],
                [
                    'label' => 'Teachers',
                    'url' => route('filament.admin.resources.teachers.teachers.index'),
                    'icon' => 'heroicon-o-user-group',
                    'color' => 'danger',
                ],
            ];
        }

        $iconMap = [
            'Class Timetable Designer' => ['icon' => 'heroicon-o-calendar-days', 'color' => 'primary'],
            'Teacher Requirements' => ['icon' => 'heroicon-o-clipboard-document-check', 'color' => 'success'],
            'Class Subject Settings' => ['icon' => 'heroicon-o-adjustments-horizontal', 'color' => 'warning'],
            'Class Rooms' => ['icon' => 'heroicon-o-academic-cap', 'color' => 'info'],
            'Teachers' => ['icon' => 'heroicon-o-user-group', 'color' => 'danger'],
            'Subjects' => ['icon' => 'heroicon-o-book-open', 'color' => 'info'],
            'Academic Terms' => ['icon' => 'heroicon-o-calendar', 'color' => 'primary'],
            'Timetable Settings' => ['icon' => 'heroicon-o-cog-6-tooth', 'color' => 'gray'],
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
