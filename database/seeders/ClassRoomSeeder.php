<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use Illuminate\Database\Seeder;

class ClassRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            // Class 1-4 (Basic 1-3 level)
            ['name' => 'Class 1', 'section' => 'A', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 1', 'section' => 'B', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 2', 'section' => 'A', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 2', 'section' => 'B', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 3', 'section' => 'A', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 3', 'section' => 'B', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 4', 'section' => 'A', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 4', 'section' => 'B', 'weekly_periods' => 40, 'total_subjects' => 8, 'status' => 'active'],

            // Class 5-7 (Basic 4-8 level)
            ['name' => 'Class 5', 'section' => 'A', 'weekly_periods' => 45, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 5', 'section' => 'B', 'weekly_periods' => 45, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 6', 'section' => 'A', 'weekly_periods' => 45, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 6', 'section' => 'B', 'weekly_periods' => 45, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 7', 'section' => 'A', 'weekly_periods' => 45, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 7', 'section' => 'B', 'weekly_periods' => 45, 'total_subjects' => 8, 'status' => 'active'],

            // Class 8 (Basic 4-8 level)
            ['name' => 'Class 8', 'section' => 'A', 'weekly_periods' => 48, 'total_subjects' => 9, 'status' => 'active'],
            ['name' => 'Class 8', 'section' => 'B', 'weekly_periods' => 48, 'total_subjects' => 9, 'status' => 'active'],

            // Class 9-10 (Secondary 9-10 level)
            ['name' => 'Class 9', 'section' => 'A', 'weekly_periods' => 48, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 9', 'section' => 'B', 'weekly_periods' => 48, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 10', 'section' => 'A', 'weekly_periods' => 48, 'total_subjects' => 8, 'status' => 'active'],
            ['name' => 'Class 10', 'section' => 'B', 'weekly_periods' => 48, 'total_subjects' => 8, 'status' => 'active'],
        ];

        foreach ($classes as $class) {
            ClassRoom::updateOrCreate(
                ['name' => $class['name'], 'section' => $class['section']],
                $class
            );
        }

        $this->command->info('ClassRoom seeder completed: '.count($classes).' classes created/updated.');
    }
}
