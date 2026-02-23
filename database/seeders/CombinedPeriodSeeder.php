<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class CombinedPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        if (! $activeTerm) {
            $this->command->info('No active academic term found. Skipping combined period seeding.');

            return;
        }

        // Get all subjects with 'combined' class subject settings
        $combinedSettings = ClassSubjectSetting::where('single_combined', 'combined')
            ->with('subject')
            ->get();

        // Get unique combined subjects
        $combinedSubjects = $combinedSettings->pluck('subject')->filter()->unique('id');

        if ($combinedSubjects->isEmpty()) {
            $this->command->info('No combined subjects found. Skipping combined period seeding.');

            return;
        }

        // Group classes by their name
        $classGroups = ClassRoom::where('status', 'active')
            ->get()
            ->groupBy(function ($class) {
                return $class->name;
            });

        $createdCount = 0;
        $periodCounter = 0;

        // For each class group, assign combined subjects sequentially
        foreach ($classGroups as $baseName => $sections) {
            if ($sections->count() < 2) {
                continue; // Need at least 2 sections for combined period
            }

            $classRoomIds = $sections->take(2)->pluck('id')->toArray();
            $subjectIndex = 0;

            // Assign each combined subject to a unique time slot for this class
            foreach ($combinedSubjects as $subject) {
                // Get teacher for this subject
                $teacher = Teacher::whereJsonContains('subject_ids', $subject->id)->first();

                if (! $teacher) {
                    $this->command->warn("No teacher found for combined subject: {$subject->name}");

                    continue;
                }

                // Check if combined period already exists for this class and subject
                $exists = CombinedPeriod::where('subject_id', $subject->id)
                    ->where('teacher_id', $teacher->id)
                    ->where('academic_term_id', $activeTerm->id)
                    ->where(function ($query) use ($classRoomIds) {
                        foreach ($classRoomIds as $id) {
                            $query->orWhereJsonContains('class_room_ids', $id);
                        }
                    })
                    ->exists();

                if ($exists) {
                    $subjectIndex++;

                    continue;
                }

                // Calculate unique day/period combination
                $dayOffset = $periodCounter % 5; // 0-4 (5 days)
                $periodOffset = 6 + (int) floor($periodCounter / 5) % 3; // Periods 6, 7, 8

                CombinedPeriod::create([
                    'name' => "{$baseName} Combined {$subject->name}",
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'class_room_ids' => $classRoomIds,
                    'day' => $dayOffset,
                    'period' => $periodOffset,
                    'frequency' => 'weekly',
                    'academic_term_id' => $activeTerm->id,
                ]);

                $createdCount++;
                $periodCounter++;
                $this->command->info("Created: {$baseName} Combined {$subject->name} (Day: {$dayOffset}, Period: {$periodOffset})");
                $subjectIndex++;
            }
        }

        if ($createdCount > 0) {
            $this->command->info("Combined periods seeded successfully! Created {$createdCount} combined periods.");
        } else {
            $this->command->info('No new combined periods were created.');
        }
    }
}
