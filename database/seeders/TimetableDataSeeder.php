<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class TimetableDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding class subject settings...');

        $this->seedClassSubjectSettings();

        $this->command->info('Timetable data seeding completed!');
    }

    private function seedClassSubjectSettings(): void
    {
        $classes = ClassRoom::active()->get();

        if ($classes->isEmpty()) {
            $this->command->warn('No active classes found. Skipping class subject settings.');

            return;
        }

        $createdCount = 0;

        foreach ($classes as $class) {
            $subjects = Subject::where('class_room_id', $class->id)
                ->where('status', 'active')
                ->get();

            if ($subjects->isEmpty()) {
                $this->command->warn("No subjects found for {$class->full_name}");

                continue;
            }

            foreach ($subjects as $subject) {
                $priority = match ($subject->type) {
                    'core' => 9,
                    'optional' => 5,
                    'co-curricular' => 3,
                    default => 5,
                };

                ClassSubjectSetting::updateOrCreate(
                    [
                        'class_room_id' => $class->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'min_periods_per_week' => 1,
                        'max_periods_per_week' => 6,
                        'weekly_periods' => 4,
                        'single_combined' => 'single',
                        'is_active' => true,
                        'priority' => $priority,
                    ]
                );

                $createdCount++;
            }

            $this->command->info("Settings created for {$class->full_name} ({$subjects->count()} subjects)");
        }

        $this->command->info("Total class subject settings: {$createdCount}");
    }
}
