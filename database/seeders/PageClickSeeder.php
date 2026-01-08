<?php

namespace Database\Seeders;

use App\Models\PageClick;
use Illuminate\Database\Seeder;

class PageClickSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page_name' => 'Class Timetable Designer',
                'url' => route('timetable-designer'),
                'click_count' => 0,
            ],
            [
                'page_name' => 'Teacher Requirements',
                'url' => route('filament.admin.pages.teacher-requirements'),
                'click_count' => 0,
            ],
            [
                'page_name' => 'Class Subject Settings',
                'url' => route('filament.admin.resources.class-subject-settings.index'),
                'click_count' => 0,
            ],
            [
                'page_name' => 'Class Rooms',
                'url' => route('filament.admin.resources.class-rooms.class-rooms.index'),
                'click_count' => 0,
            ],
            [
                'page_name' => 'Teachers',
                'url' => route('filament.admin.resources.teachers.teachers.index'),
                'click_count' => 0,
            ],
        ];

        foreach ($pages as $page) {
            PageClick::firstOrCreate(
                ['url' => $page['url']],
                $page
            );
        }
    }
}
