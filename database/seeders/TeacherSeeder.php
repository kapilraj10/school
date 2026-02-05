<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Truncate tables to start fresh
        DB::table('teacher_subject')->truncate();
        DB::table('teachers')->truncate();

        $this->command->info('Truncated teachers and pivot tables.');

        $timestamp = now();
        $teacherCount = 1;

        // 2. Get active Grade Levels (grouped by name, e.g., "Class 1")
        // We get all active classes and group them.
        $gradeLevels = ClassRoom::where('status', 'active')
            ->orderBy('name')
            ->orderBy('section')
            ->get()
            ->groupBy('name');

        $this->command->info('Creating teachers assigned by Subject and Grade Level...');

        foreach ($gradeLevels as $gradeName => $classes) {
            $this->command->info("Processing Grade Level: {$gradeName}");

            // Collect all class IDs for this grade level (e.g., 1A, 1B, 1C)
            $classIds = $classes->pluck('id')->values()->toArray();
            $sectionNames = $classes->pluck('section')->join(', ');

            // 3. Find all distinct subjects taught in this grade level
            // We look at settings for ANY of the classes in this grade.
            // Ideally, they should be uniform, but we'll take the union of subjects.
            $subjectIds = ClassSubjectSetting::whereIn('class_room_id', $classIds)
                ->where('is_active', true)
                ->pluck('subject_id')
                ->unique();

            if ($subjectIds->isEmpty()) {
                $this->command->warn("  No subjects configured for {$gradeName}. Skipping.");

                continue;
            }

            $subjects = Subject::whereIn('id', $subjectIds)
                ->where('status', 'active')
                ->get();

            foreach ($subjects as $subject) {
                // 4. Create ONE teacher for this (Subject, Grade) combination
                $teacherCode = sprintf('T%03d', $teacherCount);
                // Name format: "English Teacher (Class 1)"
                $teacherName = "{$subject->name} Teacher ({$gradeName})";

                // Determine availability matrix based on subject type
                // (Preserving the logic from the previous seeder for realism)
                $availableDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                if ($subject->type === 'co_curricular') {
                    $availableDays = ['Sun', 'Tue', 'Thu', 'Fri'];
                }

                $availabilityMatrix = $this->generateAvailabilityMatrix($availableDays);

                // Create the teacher using the Factory
                $teacher = Teacher::factory()->create([
                    'name' => $teacherName,
                    'employee_id' => $teacherCode,
                    'email' => strtolower($teacherCode).'@school.edu',
                    // Factory provides phone, but let's keep it sequential or random as per factory default?
                    // User asked to establish full profile columns. Factory handles most.
                    // We'll override specific ones to match our deterministic seeding pattern if desired,
                    // but Factory 'phone' is fake()->numerify('##########'). Let's stick to consistent data for demo.
                    'phone' => '98'.str_pad($teacherCount, 8, '0', STR_PAD_LEFT),
                    'subject_ids' => [$subject->id], // JSON column
                    'class_room_ids' => $classIds,   // JSON column: Assign to ALL sections of this grade
                    'availability_matrix' => $availabilityMatrix, // Array cast handles JSON encoding
                    'status' => 'active',
                ]);

                // 5. Populate the Pivot Table
                // The relationship is Many-to-Many.
                $teacher->subjects()->attach($subject->id, [
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $this->command->info("  Created {$teacher->name} -> Assigned to {$gradeName} [Sections: {$sectionNames}]");

                $teacherCount++;
            }
        }

        $totalTeachers = $teacherCount - 1;
        $this->command->info("\nSuccessfully seeded {$totalTeachers} teachers.");
    }

    /**
     * Generate availability matrix.
     */
    private function generateAvailabilityMatrix(array $days): array
    {
        $matrix = [];
        // Assuming 8 periods
        $periods = [1, 2, 3, 4, 5, 6, 7, 8];

        foreach ($days as $day) {
            $matrix[$day] = [];
            foreach ($periods as $period) {
                $matrix[$day][$period] = true;
            }
        }

        return $matrix;
    }
}
