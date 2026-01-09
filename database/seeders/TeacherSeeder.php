<?php

namespace Database\Seeders;

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

        // Teacher data from TeacherData.php
        $teachersData = [
            // Class 1-4 Teachers
            ['name' => 'T001', 'subject' => 'English', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T002', 'subject' => 'Nepali', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T003', 'subject' => 'Mathematics', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T004', 'subject' => 'Science', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T005', 'subject' => 'Serofero', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T006', 'subject' => 'Computer', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T007', 'subject' => 'Moral Education', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T008', 'subject' => 'General Knowledge', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T009', 'subject' => 'Dance', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T010', 'subject' => 'Music', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T011', 'subject' => 'Art', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T012', 'subject' => 'Sports', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T013', 'subject' => 'Taekwondo', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Tue', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T014', 'subject' => 'Library', 'class_range' => '1 - 4', 'available_days' => ['Sun', 'Mon', 'Wed'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],

            // Class 5-7 Teachers
            ['name' => 'T015', 'subject' => 'English', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T016', 'subject' => 'Nepali', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T017', 'subject' => 'Mathematics', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T018', 'subject' => 'Science', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T019', 'subject' => 'Social Studies', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T020', 'subject' => 'Health & Physical Education', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T021', 'subject' => 'Nepal Bhasa', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T022', 'subject' => 'Computer', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T023', 'subject' => 'Dance', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T024', 'subject' => 'Music', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T025', 'subject' => 'Art', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T026', 'subject' => 'Sports', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T027', 'subject' => 'Taekwondo', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Tue', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T028', 'subject' => 'Library', 'class_range' => '5 - 7', 'available_days' => ['Sun', 'Mon', 'Wed'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],

            // Class 8 Teachers
            ['name' => 'T029', 'subject' => 'English', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T030', 'subject' => 'Nepali', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T031', 'subject' => 'Mathematics', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T032', 'subject' => 'Science', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T033', 'subject' => 'Social Studies', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T034', 'subject' => 'Health & Physical Education', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T035', 'subject' => 'Optional Mathematics', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T036', 'subject' => 'Nepal Bhasa', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T037', 'subject' => 'Computer', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T038', 'subject' => 'Dance/Music', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T039', 'subject' => 'Art', 'class_range' => '8', 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T040', 'subject' => 'Sports', 'class_range' => '8', 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],

            // Class 9-10 Teachers
            ['name' => 'T041', 'subject' => 'English', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T042', 'subject' => 'Nepali', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T043', 'subject' => 'Mathematics', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T044', 'subject' => 'Science', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T045', 'subject' => 'Social Studies', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T046', 'subject' => 'Optional Mathematics', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T047', 'subject' => 'Computer/Accountancy', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T048', 'subject' => 'Dance/Music/Art', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T049', 'subject' => 'Sports', 'class_range' => '9 - 10', 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
        ];

        $dayToUnavailablePeriods = [
            'Sun' => [],
            'Mon' => [],
            'Tue' => [],
            'Wed' => [],
            'Thu' => [],
            'Fri' => [],
        ];

        $timestamp = now();

        foreach ($teachersData as $teacherData) {
            // Find the subject ID
            $subject = Subject::where('name', $teacherData['subject'])
                ->where('class_range', $teacherData['class_range'])
                ->first();

            if (! $subject) {
                $this->command->warn("Subject '{$teacherData['subject']}' for class range '{$teacherData['class_range']}' not found. Skipping teacher {$teacherData['name']}.");

                continue;
            }

            // Calculate available periods based on available days and periods
            $availableDays = $teacherData['available_days'];
            $availablePeriods = array_map('intval', $teacherData['available_periods']);

            // Insert teacher record
            $teacherId = DB::table('teachers')->insertGetId([
                'name' => $teacherData['name'],
                'employee_id' => strtoupper($teacherData['name']),
                'email' => strtolower($teacherData['name']).'@school.edu',
                'phone' => '98'.str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'subject_ids' => json_encode([$subject->id]),
                'max_periods_per_day' => 8,
                'max_periods_per_week' => 40,
                'available_days' => json_encode($availableDays),
                'available_periods' => json_encode($availablePeriods),
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
        }

        $this->command->info('Successfully seeded '.count($teachersData).' teachers with subject assignments and availability.');
    }
}
