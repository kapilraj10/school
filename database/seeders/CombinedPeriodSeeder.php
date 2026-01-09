<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
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

        $peSubject = Subject::where('name', 'like', '%Physical Education%')->orWhere('name', 'like', '%PE%')->first();
        $artSubject = Subject::where('name', 'like', '%Art%')->first();

        $class1Sections = ClassRoom::where('name', 'Class 1')->where('is_active', true)->get();

        if ($peSubject && $class1Sections->count() >= 2) {
            $teacher = Teacher::whereJsonContains('subject_ids', $peSubject->id)->first();

            if ($teacher && $class1Sections->count() >= 2) {
                CombinedPeriod::create([
                    'name' => 'Class 1 Combined PE',
                    'subject_id' => $peSubject->id,
                    'teacher_id' => $teacher->id,
                    'class_room_ids' => $class1Sections->take(2)->pluck('id')->toArray(),
                    'day' => 1,
                    'period' => 6,
                    'frequency' => 'weekly',
                    'academic_term_id' => $activeTerm->id,
                ]);
            }
        }

        $class2Sections = ClassRoom::where('name', 'Class 2')->where('is_active', true)->get();

        if ($artSubject && $class2Sections->count() >= 2) {
            $teacher = Teacher::whereJsonContains('subject_ids', $artSubject->id)->first();

            if ($teacher && $class2Sections->count() >= 2) {
                CombinedPeriod::create([
                    'name' => 'Class 2 Combined Art',
                    'subject_id' => $artSubject->id,
                    'teacher_id' => $teacher->id,
                    'class_room_ids' => $class2Sections->take(2)->pluck('id')->toArray(),
                    'day' => 3,
                    'period' => 7,
                    'frequency' => 'weekly',
                    'academic_term_id' => $activeTerm->id,
                ]);
            }
        }

        $this->command->info('Combined periods seeded successfully!');
    }
}
