<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // Create Academic Term
        AcademicTerm::firstOrCreate(
            ['name' => '2024-2025 Term 1'],
            [
                'year' => 2024,
                'term' => '1',
                'start_date' => '2024-09-01',
                'end_date' => '2024-12-20',
                'is_active' => true,
                'status' => 'active',
            ]
        );

        // Create Subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH101', 'type' => 'core', 'weekly_periods' => 5, 'level' => 'all', 'status' => 'active'],
            ['name' => 'English', 'code' => 'ENG101', 'type' => 'core', 'weekly_periods' => 5, 'level' => 'all', 'status' => 'active'],
            ['name' => 'Science', 'code' => 'SCI101', 'type' => 'core', 'weekly_periods' => 4, 'level' => 'all', 'status' => 'active'],
            ['name' => 'Social Studies', 'code' => 'SS101', 'type' => 'core', 'weekly_periods' => 3, 'level' => 'all', 'status' => 'active'],
            ['name' => 'Physical Education', 'code' => 'PE101', 'type' => 'co_curricular', 'weekly_periods' => 2, 'level' => 'all', 'status' => 'active'],
            ['name' => 'Art', 'code' => 'ART101', 'type' => 'elective', 'weekly_periods' => 2, 'level' => 'junior', 'status' => 'active'],
            ['name' => 'Music', 'code' => 'MUS101', 'type' => 'elective', 'weekly_periods' => 2, 'level' => 'junior', 'status' => 'active'],
            ['name' => 'Computer Science', 'code' => 'CS101', 'type' => 'core', 'weekly_periods' => 3, 'level' => 'senior', 'status' => 'active'],
            ['name' => 'Physics', 'code' => 'PHY101', 'type' => 'core', 'weekly_periods' => 4, 'level' => 'senior', 'status' => 'active'],
            ['name' => 'Chemistry', 'code' => 'CHM101', 'type' => 'core', 'weekly_periods' => 4, 'level' => 'senior', 'status' => 'active'],
        ];

        $subjectIds = [];
        foreach ($subjects as $subjectData) {
            $subject = Subject::firstOrCreate(
                ['code' => $subjectData['code']],
                $subjectData
            );
            $subjectIds[$subject->code] = $subject->id;
        }

        // Create Teachers
        $teachers = [
            ['name' => 'John Smith', 'employee_id' => 'EMP001', 'email' => 'john.smith@school.com', 'phone' => '1234567890', 'subjects' => ['MATH101', 'SCI101']],
            ['name' => 'Jane Doe', 'employee_id' => 'EMP002', 'email' => 'jane.doe@school.com', 'phone' => '1234567891', 'subjects' => ['ENG101']],
            ['name' => 'Robert Johnson', 'employee_id' => 'EMP003', 'email' => 'robert.j@school.com', 'phone' => '1234567892', 'subjects' => ['SCI101', 'PHY101']],
            ['name' => 'Emily Williams', 'employee_id' => 'EMP004', 'email' => 'emily.w@school.com', 'phone' => '1234567893', 'subjects' => ['SS101', 'ENG101']],
            ['name' => 'Michael Brown', 'employee_id' => 'EMP005', 'email' => 'michael.b@school.com', 'phone' => '1234567894', 'subjects' => ['PE101']],
            ['name' => 'Sarah Davis', 'employee_id' => 'EMP006', 'email' => 'sarah.d@school.com', 'phone' => '1234567895', 'subjects' => ['ART101', 'MUS101']],
            ['name' => 'David Miller', 'employee_id' => 'EMP007', 'email' => 'david.m@school.com', 'phone' => '1234567896', 'subjects' => ['CS101', 'MATH101']],
            ['name' => 'Lisa Anderson', 'employee_id' => 'EMP008', 'email' => 'lisa.a@school.com', 'phone' => '1234567897', 'subjects' => ['CHM101', 'SCI101']],
        ];

        foreach ($teachers as $teacherData) {
            $teacherSubjectIds = array_map(fn($code) => $subjectIds[$code] ?? null, $teacherData['subjects']);
            $teacherSubjectIds = array_filter($teacherSubjectIds);

            Teacher::firstOrCreate(
                ['employee_id' => $teacherData['employee_id']],
                [
                    'name' => $teacherData['name'],
                    'email' => $teacherData['email'],
                    'phone' => $teacherData['phone'],
                    'subject_ids' => array_values($teacherSubjectIds),
                    'max_periods_per_day' => 6,
                    'max_periods_per_week' => 25,
                    'unavailable_periods' => [],
                    'status' => 'active',
                ]
            );
        }

        // Create Classes
        $classes = [
            ['name' => 'Grade 6', 'section' => 'A', 'level' => 'basic_4_8', 'weekly_periods' => 35, 'total_subjects' => 8],
            ['name' => 'Grade 6', 'section' => 'B', 'level' => 'basic_4_8', 'weekly_periods' => 35, 'total_subjects' => 8],
            ['name' => 'Grade 7', 'section' => 'A', 'level' => 'basic_4_8', 'weekly_periods' => 35, 'total_subjects' => 8],
            ['name' => 'Grade 7', 'section' => 'B', 'level' => 'basic_4_8', 'weekly_periods' => 35, 'total_subjects' => 8],
            ['name' => 'Grade 8', 'section' => 'A', 'level' => 'basic_4_8', 'weekly_periods' => 40, 'total_subjects' => 10],
            ['name' => 'Grade 8', 'section' => 'B', 'level' => 'basic_4_8', 'weekly_periods' => 40, 'total_subjects' => 10],
            ['name' => 'Grade 9', 'section' => 'A', 'level' => 'secondary_9_10', 'weekly_periods' => 40, 'total_subjects' => 10],
            ['name' => 'Grade 9', 'section' => 'B', 'level' => 'secondary_9_10', 'weekly_periods' => 40, 'total_subjects' => 10],
        ];

        foreach ($classes as $classData) {
            ClassRoom::firstOrCreate(
                ['name' => $classData['name'], 'section' => $classData['section']],
                array_merge($classData, ['status' => 'active'])
            );
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin login: admin@school.com / password');
    }
}