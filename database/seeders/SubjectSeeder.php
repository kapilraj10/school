<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        Subject::query()->delete();

        $subjectTemplates = [
            [
                'classes' => [1, 2, 3, 4],
                'subjects' => [
                    ['name' => 'English', 'code_prefix' => 'ENG', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Nepali', 'code_prefix' => 'NEP', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Mathematics', 'code_prefix' => 'MATH', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Science', 'code_prefix' => 'SCI', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Serofero', 'code_prefix' => 'SERO', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 8, 'min_periods_per_week' => 8, 'max_periods_per_week' => 8, 'single_combined' => 'single'],
                    ['name' => 'Computer', 'code_prefix' => 'COMP', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
                    ['name' => 'Moral Education', 'code_prefix' => 'MORAL', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
                    ['name' => 'General Knowledge', 'code_prefix' => 'GK', 'type' => 'core', 'level' => 'primary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
                    ['name' => 'Dance', 'code_prefix' => 'DANCE', 'type' => 'co_curricular', 'level' => 'primary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Music', 'code_prefix' => 'MUSIC', 'type' => 'co_curricular', 'level' => 'primary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Art', 'code_prefix' => 'ART', 'type' => 'co_curricular', 'level' => 'primary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Sports', 'code_prefix' => 'SPORT', 'type' => 'co_curricular', 'level' => 'primary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                    ['name' => 'Taekwondo', 'code_prefix' => 'TKD', 'type' => 'co_curricular', 'level' => 'primary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                    ['name' => 'Library', 'code_prefix' => 'LIB', 'type' => 'co_curricular', 'level' => 'primary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'single'],
                ],
            ],
            [
                'classes' => [5, 6, 7],
                'subjects' => [
                    ['name' => 'English', 'code_prefix' => 'ENG', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Nepali', 'code_prefix' => 'NEP', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Mathematics', 'code_prefix' => 'MATH', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Science', 'code_prefix' => 'SCI', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Social Studies', 'code_prefix' => 'SOCIAL', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Health & Physical Education', 'code_prefix' => 'HPE', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Nepal Bhasa', 'code_prefix' => 'NBHASA', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
                    ['name' => 'Computer', 'code_prefix' => 'COMP', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
                    ['name' => 'Dance', 'code_prefix' => 'DANCE', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Music', 'code_prefix' => 'MUSIC', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Art', 'code_prefix' => 'ART', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Sports', 'code_prefix' => 'SPORT', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                    ['name' => 'Taekwondo', 'code_prefix' => 'TKD', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                    ['name' => 'Library', 'code_prefix' => 'LIB', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'single'],
                ],
            ],
            [
                'classes' => [8],
                'subjects' => [
                    ['name' => 'English', 'code_prefix' => 'ENG', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Nepali', 'code_prefix' => 'NEP', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Mathematics', 'code_prefix' => 'MATH', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Science', 'code_prefix' => 'SCI', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Social Studies', 'code_prefix' => 'SOCIAL', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Health & Physical Education', 'code_prefix' => 'HPE', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Optional Mathematics', 'code_prefix' => 'OPTMATH', 'type' => 'core', 'level' => 'senior', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Nepal Bhasa', 'code_prefix' => 'NBHASA', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 3, 'single_combined' => 'single'],
                    ['name' => 'Computer', 'code_prefix' => 'COMP', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
                    ['name' => 'Dance/Music', 'code_prefix' => 'DANCEMUSIC', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                    ['name' => 'Art', 'code_prefix' => 'ART', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 1, 'min_periods_per_week' => 1, 'max_periods_per_week' => 1, 'single_combined' => 'combined'],
                    ['name' => 'Sports', 'code_prefix' => 'SPORT', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                ],
            ],
            [
                'classes' => [9, 10],
                'subjects' => [
                    ['name' => 'English', 'code_prefix' => 'ENG', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Nepali', 'code_prefix' => 'NEP', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 6, 'single_combined' => 'single'],
                    ['name' => 'Mathematics', 'code_prefix' => 'MATH', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 7, 'single_combined' => 'single'],
                    ['name' => 'Science', 'code_prefix' => 'SCI', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 7, 'single_combined' => 'single'],
                    ['name' => 'Social Studies', 'code_prefix' => 'SOCIAL', 'type' => 'core', 'level' => 'secondary', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 7, 'single_combined' => 'single'],
                    ['name' => 'Optional Mathematics', 'code_prefix' => 'OPTMATH', 'type' => 'core', 'level' => 'senior', 'weekly_periods' => 5, 'min_periods_per_week' => 5, 'max_periods_per_week' => 7, 'single_combined' => 'single'],
                    ['name' => 'Computer/Accountancy', 'code_prefix' => 'COMPACC', 'type' => 'elective', 'level' => 'senior', 'weekly_periods' => 3, 'min_periods_per_week' => 3, 'max_periods_per_week' => 4, 'single_combined' => 'single'],
                    ['name' => 'Dance/Music/Art', 'code_prefix' => 'DMUSICART', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                    ['name' => 'Sports', 'code_prefix' => 'SPORT', 'type' => 'co_curricular', 'level' => 'secondary', 'weekly_periods' => 2, 'min_periods_per_week' => 2, 'max_periods_per_week' => 2, 'single_combined' => 'combined'],
                ],
            ],
        ];

        $timestamp = now();
        $createdCount = 0;

        foreach ($subjectTemplates as $template) {
            $classes = $template['classes'];
            $subjects = $template['subjects'];

            foreach ($classes as $classNumber) {
                $classRooms = ClassRoom::where('name', 'Class '.$classNumber)->get();

                foreach ($classRooms as $classRoom) {
                    foreach ($subjects as $subjectData) {
                        $subject = Subject::create([
                            'name' => $subjectData['name'],
                            'code' => "{$subjectData['code_prefix']}-{$classNumber}{$classRoom->section}",
                            'class_room_id' => $classRoom->id,
                            'type' => $subjectData['type'],
                            'level' => $subjectData['level'],
                            'status' => 'active',
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);

                        ClassSubjectSetting::create([
                            'class_room_id' => $classRoom->id,
                            'subject_id' => $subject->id,
                            'weekly_periods' => $subjectData['weekly_periods'],
                            'min_periods_per_week' => $subjectData['min_periods_per_week'],
                            'max_periods_per_week' => $subjectData['max_periods_per_week'],
                            'single_combined' => $subjectData['single_combined'],
                            'is_active' => true,
                            'priority' => match ($subjectData['type']) {
                                'core' => 9,
                                'elective', 'optional' => 5,
                                'co_curricular', 'co-curricular' => 3,
                                default => 5,
                            },
                        ]);

                        $createdCount++;
                    }
                }
            }
        }

        $this->command->info("Successfully seeded {$createdCount} subjects for individual classes.");
    }
}
