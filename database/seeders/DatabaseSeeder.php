<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        $this->call([
            TimetableSettingsSeeder::class,
            ClassRoomSeeder::class,
            SubjectSeeder::class,
            TimetableDataSeeder::class,
            TeacherSeeder::class,
        ]);

        $this->seedUsers();
        $this->call(RolesAndPermissionsSeeder::class);
        $this->seedAcademicTerms();
        $this->seedPageClicks();

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin login: admin@admin.com / password');
    }

    private function seedUsers(): void
    {
        $this->command->info('Seeding users...');

        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        $this->command->info('Users seeded.');
    }

    private function seedAcademicTerms(): void
    {
        $this->command->info('Seeding academic terms...');

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

        AcademicTerm::firstOrCreate(
            ['name' => '2024-2025 Term 2'],
            [
                'year' => 2024,
                'term' => '2',
                'start_date' => '2025-01-15',
                'end_date' => '2025-05-30',
                'is_active' => false,
                'status' => 'draft',
            ]
        );

        AcademicTerm::firstOrCreate(
            ['name' => '2025-2026 Term 1'],
            [
                'year' => 2025,
                'term' => '1',
                'start_date' => '2025-09-01',
                'end_date' => '2025-12-20',
                'is_active' => false,
                'status' => 'draft',
            ]
        );

        $this->command->info('Academic terms seeded.');
    }

    private function seedPageClicks(): void
    {
        $this->command->info('Seeding page clicks...');

        try {
            $this->call(PageClickSeeder::class);
        } catch (\Exception $e) {
            $this->command->warn('Page clicks seeding skipped: '.$e->getMessage());
        }

        $this->command->info('Page clicks seeding completed.');
    }
}
