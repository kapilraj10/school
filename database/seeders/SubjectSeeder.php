<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing subjects
        DB::table('subjects')->truncate();

        // Based on SubjectData.php from storage/Algorithm
        $subjectsData = [
            // Classes 1-4
            ['name' => 'English', 'code' => 'ENG-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Nepali', 'code' => 'NEP-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Mathematics', 'code' => 'MATH-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Science', 'code' => 'SCI-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Serofero', 'code' => 'SERO-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'junior', 'weekly_periods' => 8, 'min_periods_per_week' => 8, 'max_periods_per_week' => 8, 'single_combined' => 'single'],
            ['name' => 'Computer', 'code' => 'COMP-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
            ['name' => 'Moral Education', 'code' => 'MORAL-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'junior', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
            ['name' => 'General Knowledge', 'code' => 'GK-1-4', 'class_range' => '1 - 4', 'type' => 'core', 'level' => 'junior', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
            ['name' => 'Dance', 'code' => 'DANCE-1-4', 'class_range' => '1 - 4', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Music', 'code' => 'MUSIC-1-4', 'class_range' => '1 - 4', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Art', 'code' => 'ART-1-4', 'class_range' => '1 - 4', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Sports', 'code' => 'SPORT-1-4', 'class_range' => '1 - 4', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
            ['name' => 'Taekwondo', 'code' => 'TKD-1-4', 'class_range' => '1 - 4', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
            ['name' => 'Library', 'code' => 'LIB-1-4', 'class_range' => '1 - 4', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'single'],

            // Classes 5-7
            ['name' => 'English', 'code' => 'ENG-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Nepali', 'code' => 'NEP-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Mathematics', 'code' => 'MATH-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Science', 'code' => 'SCI-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Social Studies', 'code' => 'SOCIAL-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Health & Physical Education', 'code' => 'HPE-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Nepal Bhasa', 'code' => 'NBHASA-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
            ['name' => 'Computer', 'code' => 'COMP-5-7', 'class_range' => '5 - 7', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
            ['name' => 'Dance', 'code' => 'DANCE-5-7', 'class_range' => '5 - 7', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Music', 'code' => 'MUSIC-5-7', 'class_range' => '5 - 7', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Art', 'code' => 'ART-5-7', 'class_range' => '5 - 7', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Sports', 'code' => 'SPORT-5-7', 'class_range' => '5 - 7', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
            ['name' => 'Taekwondo', 'code' => 'TKD-5-7', 'class_range' => '5 - 7', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
            ['name' => 'Library', 'code' => 'LIB-5-7', 'class_range' => '5 - 7', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'single'],

            // Class 8
            ['name' => 'English', 'code' => 'ENG-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Nepali', 'code' => 'NEP-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Mathematics', 'code' => 'MATH-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Science', 'code' => 'SCI-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Social Studies', 'code' => 'SOCIAL-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Health & Physical Education', 'code' => 'HPE-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Optional Mathematics', 'code' => 'OPTMATH-8', 'class_range' => '8', 'type' => 'core', 'level' => 'senior', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Nepal Bhasa', 'code' => 'NBHASA-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
            ['name' => 'Computer', 'code' => 'COMP-8', 'class_range' => '8', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
            ['name' => 'Dance/Music', 'code' => 'DANCEMUSIC-8', 'class_range' => '8', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
            ['name' => 'Art', 'code' => 'ART-8', 'class_range' => '8', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
            ['name' => 'Sports', 'code' => 'SPORT-8', 'class_range' => '8', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],

            // Classes 9-10
            ['name' => 'English', 'code' => 'ENG-9-10', 'class_range' => '9 - 10', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Nepali', 'code' => 'NEP-9-10', 'class_range' => '9 - 10', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Mathematics', 'code' => 'MATH-9-10', 'class_range' => '9 - 10', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Science', 'code' => 'SCI-9-10', 'class_range' => '9 - 10', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Social Studies', 'code' => 'SOCIAL-9-10', 'class_range' => '9 - 10', 'type' => 'core', 'level' => 'all', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Optional Mathematics', 'code' => 'OPTMATH-9-10', 'class_range' => '9 - 10', 'type' => 'core', 'level' => 'senior', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
            ['name' => 'Computer/Accountancy', 'code' => 'COMPACC-9-10', 'class_range' => '9 - 10', 'type' => 'elective', 'level' => 'senior', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
            ['name' => 'Dance/Music/Art', 'code' => 'DMUSICART-9-10', 'class_range' => '9 - 10', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
            ['name' => 'Sports', 'code' => 'SPORT-9-10', 'class_range' => '9 - 10', 'type' => 'co_curricular', 'level' => 'all', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
        ];

        $timestamp = now();

        foreach ($subjectsData as &$subject) {
            $subject['status'] = 'active';
            $subject['created_at'] = $timestamp;
            $subject['updated_at'] = $timestamp;
        }

        DB::table('subjects')->insert($subjectsData);

        $this->command->info('Successfully seeded '.count($subjectsData).' subjects with class-specific requirements.');
    }
}
