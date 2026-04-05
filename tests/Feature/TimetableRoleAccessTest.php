<?php

namespace Tests\Feature;

use App\Filament\Pages\TimetableViewer;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableRoleAccessTest extends TestCase
{
    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('teacher', 'web');
        Role::findOrCreate('student', 'web');

        $this->term = AcademicTerm::factory()->active()->create();
    }

    public function test_teacher_is_restricted_to_own_timetable_view(): void
    {
        $teacherUser = User::factory()->create([
            'email' => 'teacher1@example.com',
        ]);
        $teacherUser->assignRole('teacher');

        $teacher = Teacher::factory()->create([
            'email' => 'teacher1@example.com',
            'status' => 'active',
        ]);

        $otherTeacher = Teacher::factory()->create([
            'email' => 'teacher2@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($teacherUser);

        Livewire::test(TimetableViewer::class)
            ->set('data.view_type', 'class')
            ->set('data.teacher_id', $otherTeacher->id)
            ->call('loadTimetable')
            ->assertSet('data.view_type', 'teacher')
            ->assertSet('data.teacher_id', $teacher->id);
    }

    public function test_student_is_restricted_to_own_class_timetable_view(): void
    {
        $ownClass = ClassRoom::factory()->create();
        $otherClass = ClassRoom::factory()->create();

        $studentUser = User::factory()->create([
            'class_room_id' => $ownClass->id,
        ]);
        $studentUser->assignRole('student');

        $this->actingAs($studentUser);

        Livewire::test(TimetableViewer::class)
            ->set('data.view_type', 'teacher')
            ->set('data.class_room_id', $otherClass->id)
            ->call('loadTimetable')
            ->assertSet('data.view_type', 'class')
            ->assertSet('data.class_room_id', $ownClass->id);
    }

    public function test_teacher_cannot_access_class_print_preview_route(): void
    {
        $class = ClassRoom::factory()->create();
        $teacherUser = User::factory()->create([
            'email' => 'teacher-preview@example.com',
        ]);
        $teacherUser->assignRole('teacher');

        Teacher::factory()->create([
            'email' => 'teacher-preview@example.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($teacherUser)->get(route('print.class-preview', [
            'class_id' => $class->id,
            'term_id' => $this->term->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_student_cannot_access_teacher_print_preview_route(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole('student');

        $teacher = Teacher::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($studentUser)->get(route('print.teacher-preview', [
            'teacher_id' => $teacher->id,
            'term_id' => $this->term->id,
        ]));

        $response->assertStatus(403);
    }
}
