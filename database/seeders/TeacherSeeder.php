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
        // Clear existing teachers and pivot table
        DB::table('teacher_subject')->truncate();
        DB::table('teachers')->truncate();

        $timestamp = now();
        $teacherCount = 1;

        // Get all active classes
        $classes = ClassRoom::where('status', 'active')
            ->orderBy('name')
            ->orderBy('section')
            ->get();

        $this->command->info('Creating teachers for each class-subject combination...');

        foreach ($classes as $class) {
            $this->command->info("Processing {$class->name} {$class->section}...");

            // Get all active subjects for this class
            $classSubjects = ClassSubjectSetting::where('class_room_id', $class->id)
                ->where('is_active', true)
                ->with('subject')
                ->get();

            if ($classSubjects->isEmpty()) {
                $this->command->warn("  No subjects configured for {$class->name} {$class->section}. Skipping.");

                continue;
            }

            foreach ($classSubjects as $classSetting) {
                $subject = $classSetting->subject;

                if (! $subject || $subject->status !== 'active') {
                    continue;
                }

                // Generate teacher name
                $teacherCode = sprintf('T%03d', $teacherCount);
                $teacherName = $subject->name.' Teacher ('.$class->name.' '.$class->section.')';

                // Determine availability based on subject type
                $availableDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                $availablePeriods = [1, 2, 3, 4, 5, 6, 7, 8];

                // For co-curricular subjects, limit availability slightly
                if ($subject->type === 'co_curricular') {
                    $availableDays = ['Sun', 'Tue', 'Thu', 'Fri'];
                }

                // Build availability matrix
                $availabilityMatrix = [];
                foreach ($availableDays as $day) {
                    $availabilityMatrix[$day] = [];
                    foreach ($availablePeriods as $period) {
                        $availabilityMatrix[$day][$period] = true;
                    }
                }

                // Insert teacher record
                $teacherId = DB::table('teachers')->insertGetId([
                    'name' => $teacherName,
                    'employee_id' => $teacherCode,
                    'email' => strtolower($teacherCode).'@school.edu',
                    'phone' => '98'.str_pad($teacherCount, 8, '0', STR_PAD_LEFT),
                    'subject_ids' => json_encode([$subject->id]),
                    'max_periods_per_day' => 7,
                    'max_periods_per_week' => 40,
                    'availability_matrix' => json_encode($availabilityMatrix),
                    'status' => 'active',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                // Insert into teacher_subject pivot table
                DB::table('teacher_subject')->insert([
                    'teacher_id' => $teacherId,
                    'subject_id' => $subject->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $teacherCount++;
            }
        }

        $totalTeachers = $teacherCount - 1;
        $this->command->info("\nSuccessfully seeded {$totalTeachers} teachers.");
        $this->command->info('Each class-subject combination now has a dedicated teacher.');
    }
}
