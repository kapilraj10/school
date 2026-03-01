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
        // ClassSubjectSetting records are already created with correct period
        // values by SubjectSeeder. This method only corrects the priority field
        // based on subject type, since SubjectSeeder defaults everything to 5.

        $classes = ClassRoom::active()->get();

        if ($classes->isEmpty()) {
            $this->command->warn('No active classes found. Skipping class subject settings.');

            return;
        }

        $updatedCount = 0;

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
                    'elective', 'optional' => 5,
                    'co_curricular', 'co-curricular' => 3,
                    default => 5,
                };

                ClassSubjectSetting::where('class_room_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->update(['priority' => $priority, 'is_active' => true]);

                $updatedCount++;
            }

            $this->command->info("Priority updated for {$class->full_name} ({$subjects->count()} subjects)");
        }

        $this->command->info("Total class subject settings updated: {$updatedCount}");
    }
}
