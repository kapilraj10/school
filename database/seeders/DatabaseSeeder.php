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
        // Seed subjects first
        $this->call([
            SubjectSeeder::class,
        ]);

        // Seed teachers after subjects (they need subject IDs)
        $this->call([
            TeacherSeeder::class,
        ]);

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
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

        // Teachers are already seeded by TeacherSeeder

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
        $this->command->info('Admin login: admin@admin.com / password');
    }
}