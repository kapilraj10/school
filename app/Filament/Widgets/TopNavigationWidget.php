<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TopNavigationWidget extends Widget
{
    protected static string $view = 'filament.widgets.top-navigation-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function getTopItems(): array
    {
        return [
            [
                'label' => 'Class Subject Settings',
                'url' => '/admin/class-subject-settings',
                'icon' => 'heroicon-o-adjustments-horizontal',
            ],
            [
                'label' => 'Teacher Requirements',
                'url' => '/admin/teacher-requirements',
                'icon' => 'heroicon-o-clipboard-document-check',
            ],
            [
                'label' => 'Timetable Designer',
                'url' => '/timetable-designer',
                'icon' => 'heroicon-o-pencil-square',
            ],
            [
                'label' => 'General Settings',
                'url' => '/admin/timetable-settings',
                'icon' => 'heroicon-o-cog-6-tooth',
            ],
            [
                'label' => 'Classes',
                'url' => '/admin/class-rooms',
                'icon' => 'heroicon-o-academic-cap',
            ],
        ];
    }
}
