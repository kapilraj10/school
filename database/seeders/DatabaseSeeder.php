<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Subject;
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

        // Seed classes
        $this->call([
            ClassRoomSeeder::class,
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

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin login: admin@admin.com / password');
    }
}
