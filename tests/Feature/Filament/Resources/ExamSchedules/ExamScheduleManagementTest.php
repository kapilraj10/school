<?php

namespace Tests\Feature\Filament\Resources\ExamSchedules;

use App\Filament\Resources\ExamScheduleResource\Pages\CreateExamSchedule;
use App\Filament\Resources\ExamScheduleResource\Pages\EditExamSchedule;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExamScheduleManagementTest extends TestCase
{
    protected User $user;

    protected AcademicTerm $term;

    protected ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->term = AcademicTerm::factory()->active()->create();
        $this->classRoom = ClassRoom::factory()->create([
            'status' => 'active',
        ]);

        $permissions = [
            'exam_schedule.list',
            'exam_schedule.view',
            'exam_schedule.create',
            'exam_schedule.edit',
            'exam_schedule.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_create_exam_schedule(): void
    {
        Livewire::test(CreateExamSchedule::class)
            ->fillForm([
                'academic_term_id' => $this->term->id,
                'class_room_id' => $this->classRoom->id,
                'title' => 'Midterm Mathematics',
                'exam_type' => 'midterm',
                'date' => now()->addWeek()->toDateString(),
                'day_of_week' => 1,
                'is_school_wide' => false,
                'notes' => 'Class-specific exam',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('exam_schedules', [
            'academic_term_id' => $this->term->id,
            'class_room_id' => $this->classRoom->id,
            'title' => 'Midterm Mathematics',
            'exam_type' => 'midterm',
        ]);
    }

    public function test_can_edit_exam_schedule(): void
    {
        $exam = \App\Models\ExamSchedule::query()->create([
            'academic_term_id' => $this->term->id,
            'class_room_id' => $this->classRoom->id,
            'title' => 'Science Unit Test',
            'exam_type' => 'unit',
            'date' => now()->addDays(3)->toDateString(),
            'day_of_week' => 2,
            'is_school_wide' => false,
        ]);

        Livewire::test(EditExamSchedule::class, ['record' => $exam->id])
            ->fillForm([
                'academic_term_id' => $this->term->id,
                'class_room_id' => $this->classRoom->id,
                'title' => 'Science Final Exam',
                'exam_type' => 'final',
                'date' => now()->addDays(10)->toDateString(),
                'day_of_week' => 4,
                'is_school_wide' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('exam_schedules', [
            'id' => $exam->id,
            'title' => 'Science Final Exam',
            'exam_type' => 'final',
            'is_school_wide' => true,
        ]);
    }
}
