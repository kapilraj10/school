<?php

namespace Database\Seeders;

use App\Models\ClassRange;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimetableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding timetable data...');

        // Seed class ranges
        $this->seedClassRanges();

        // Seed class subject settings
        $this->seedClassSubjectSettings();

        $this->command->info('Timetable data seeding completed!');
    }

    /**
     * Seed class ranges table
     */
    private function seedClassRanges(): void
    {
        $this->command->info('Seeding class ranges...');

        DB::table('class_ranges')->truncate();

        $classRanges = [
            [
                'name' => '1 - 4',
                'display_name' => 'Class 1-4',
                'start_class' => 1,
                'end_class' => 4,
                'periods_per_day' => 8,
                'periods_per_week' => 48,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '5 - 7',
                'display_name' => 'Class 5-7',
                'start_class' => 5,
                'end_class' => 7,
                'periods_per_day' => 8,
                'periods_per_week' => 48,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '8',
                'display_name' => 'Class 8',
                'start_class' => 8,
                'end_class' => 8,
                'periods_per_day' => 8,
                'periods_per_week' => 48,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '9 - 10',
                'display_name' => 'Class 9-10',
                'start_class' => 9,
                'end_class' => 10,
                'periods_per_day' => 8,
                'periods_per_week' => 48,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($classRanges as $range) {
            ClassRange::create($range);
        }

        $this->command->info('Created '.count($classRanges).' class ranges.');
    }

    /**
     * Seed class subject settings
     */
    private function seedClassSubjectSettings(): void
    {
        $this->command->info('Seeding class subject settings...');

        // Clear existing settings
        DB::table('class_subject_settings')->truncate();

        $classes = ClassRoom::active()->get();
        $createdCount = 0;

        foreach ($classes as $class) {
            // Extract class number from name (e.g., "Class 5" => 5)
            $classNumber = (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT);

            // Get class range for this class
            $classRange = ClassRange::getForClassNumber($classNumber);

            if (! $classRange) {
                $this->command->warn("No class range found for {$class->full_name}");
                continue;
            }

            // Get all subjects for this class range
            $subjects = Subject::where('class_range', $classRange->name)
                ->where('status', 'active')
                ->get();

            if ($subjects->isEmpty()) {
                $this->command->warn("No subjects found for class range {$classRange->name}");
                continue;
            }

            // Create settings for each subject
            foreach ($subjects as $subject) {
                // Determine priority based on subject type
                $priority = match ($subject->type) {
                    'core' => 9,
                    'optional' => 5,
                    'co_curricular' => 3,
                    default => 5,
                };

                ClassSubjectSetting::create([
                    'class_room_id' => $class->id,
                    'subject_id' => $subject->id,
                    'min_periods_per_week' => $subject->min_periods_per_week ?? 1,
                    'max_periods_per_week' => $subject->max_periods_per_week ?? 6,
                    'weekly_periods' => $subject->weekly_periods ?? 4,
                    'single_combined' => $subject->single_combined ?? 'single',
                    'is_active' => true,
                    'priority' => $priority,
                ]);

                $createdCount++;
            }

            $this->command->info("Created settings for {$class->full_name} ({$subjects->count()} subjects)");
        }

        $this->command->info("Total class subject settings created: {$createdCount}");
    }
}
