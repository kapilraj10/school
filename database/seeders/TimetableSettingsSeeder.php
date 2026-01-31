<?php

namespace Database\Seeders;

use App\Models\ClassRange;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\TimetableSetting;
use Illuminate\Database\Seeder;

class TimetableSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed default class ranges
        $this->seedClassRanges();

        // Seed default timetable settings
        $this->seedTimetableSettings();

        // Sync class subject settings for all classes
        $this->syncClassSubjectSettings();
    }

    private function seedClassRanges(): void
    {
        $ranges = [
            ['name' => '1 - 4', 'display_name' => 'Class 1-4', 'start_class' => 1, 'end_class' => 4, 'sort_order' => 1],
            ['name' => '5 - 7', 'display_name' => 'Class 5-7', 'start_class' => 5, 'end_class' => 7, 'sort_order' => 2],
            ['name' => '8', 'display_name' => 'Class 8', 'start_class' => 8, 'end_class' => 8, 'sort_order' => 3],
            ['name' => '9 - 10', 'display_name' => 'Class 9-10', 'start_class' => 9, 'end_class' => 10, 'sort_order' => 4],
        ];

        foreach ($ranges as $range) {
            ClassRange::updateOrCreate(
                ['name' => $range['name']],
                array_merge($range, [
                    'periods_per_day' => 8,
                    'periods_per_week' => 48,
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Class ranges seeded successfully.');
    }

    private function seedTimetableSettings(): void
    {
        $settings = [
            // General settings
            ['key' => 'school_days', 'value' => '["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"]', 'type' => 'json', 'group' => 'general', 'description' => 'Days when school is in session'],

            // Period settings
            ['key' => 'periods_per_day', 'value' => '8', 'type' => 'integer', 'group' => 'periods', 'description' => 'Number of periods per school day'],

            // Algorithm settings
            ['key' => 'max_same_subject_per_day', 'value' => '2', 'type' => 'integer', 'group' => 'algorithm', 'description' => 'Maximum times same subject can appear per day'],
            ['key' => 'respect_teacher_availability', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Check teacher availability when assigning'],
            ['key' => 'balance_daily_load', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Distribute subjects evenly across days'],
            ['key' => 'avoid_consecutive_subjects', 'value' => '1', 'type' => 'boolean', 'group' => 'algorithm', 'description' => 'Avoid scheduling same subject back-to-back'],
        ];

        foreach ($settings as $setting) {
            TimetableSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Timetable settings seeded successfully.');
    }

    private function syncClassSubjectSettings(): void
    {
        $classes = ClassRoom::active()->get();

        foreach ($classes as $class) {
            ClassSubjectSetting::syncSubjectsForClass($class);
        }

        $this->command->info('Class subject settings synced for '.$classes->count().' classes.');
    }
}
