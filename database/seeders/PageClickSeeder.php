<?php

namespace Database\Seeders;

use App\Models\PageClick;
use Illuminate\Database\Seeder;

class PageClickSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding page clicks...');

        $pages = [
            [
                'page_name' => 'Class Timetable Designer',
                'url' => '/timetable-designer',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Teacher Requirements',
                'url' => '/admin/teacher-requirements',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Class Subject Settings',
                'url' => '/admin/class-subject-settings',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Class Rooms',
                'url' => '/admin/class-rooms/class-rooms',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Teachers',
                'url' => '/admin/teachers/teachers',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Subjects',
                'url' => '/admin/subjects/subjects',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Academic Terms',
                'url' => '/admin/academic-terms',
                'click_count' => 0,
            ],
            [
                'page_name' => 'Timetable Settings',
                'url' => '/admin/timetable-settings',
                'click_count' => 0,
            ],
        ];

        foreach ($pages as $page) {
            PageClick::updateOrCreate(
                ['url' => $page['url']],
                $page
            );
        }

        $this->command->info('Page clicks seeded: '.count($pages).' entries.');
    }
}
