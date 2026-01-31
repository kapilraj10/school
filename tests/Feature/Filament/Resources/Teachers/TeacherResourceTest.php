<?php

namespace Tests\Feature\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Teachers\Pages\ViewTeacher;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherResourceTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_teachers(): void
    {
        $teachers = Teacher::factory()->count(3)->create();

        Livewire::test(ListTeachers::class)
            ->assertCanSeeTableRecords($teachers);
    }

    public function test_can_view_teacher(): void
    {
        $teacher = Teacher::factory()->create([
            'name' => 'John Doe',
            'available_days' => ['Mon', 'Tue', 'Wed'],
            'available_periods' => [1, 2, 3, 4],
        ]);

        Livewire::test(ViewTeacher::class, ['record' => $teacher->id])
            ->assertSuccessful()
            ->assertSee('John Doe')
            ->assertSee('Monday')
            ->assertSee('Tuesday')
            ->assertSee('Period 1');
    }

    public function test_can_create_teacher_with_availability_grid(): void
    {
        $subject = Subject::factory()->create();
        $classroom = ClassRoom::factory()->create();

        Livewire::test(CreateTeacher::class)
            ->fillForm([
                'name' => 'Jane Smith',
                'employee_id' => 'EMP123',
                'email' => 'jane@example.com',
                'phone' => '1234567890',
                'status' => 'active',
                'subject_ids' => [$subject->id],
                'class_room_ids' => [$classroom->id],
                'max_periods_per_day' => 6,
                'max_periods_per_week' => 30,
                'availability' => [
                    'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'periods' => [1, 2, 3, 4, 5, 6],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $teacher = Teacher::where('email', 'jane@example.com')->first();
        $this->assertNotNull($teacher);
        $this->assertEquals(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], $teacher->available_days);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $teacher->available_periods);
    }

    public function test_can_edit_teacher_with_availability_grid(): void
    {
        $subject = Subject::factory()->create();

        $teacher = Teacher::factory()->create([
            'subject_ids' => [$subject->id],
            'available_days' => ['Mon', 'Tue'],
            'available_periods' => [1, 2],
        ]);

        Livewire::test(EditTeacher::class, ['record' => $teacher->id])
            ->assertFormSet([
                'availability' => [
                    'days' => ['Mon', 'Tue'],
                    'periods' => [1, 2],
                ],
            ])
            ->fillForm([
                'availability' => [
                    'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'periods' => [1, 2, 3, 4, 5, 6, 7, 8],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $teacher->refresh();
        $this->assertEquals(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], $teacher->available_days);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8], $teacher->available_periods);
    }

    public function test_availability_grid_displays_correctly_in_view(): void
    {
        $teacher = Teacher::factory()->create([
            'name' => 'Test Teacher',
            'available_days' => ['Mon', 'Wed', 'Fri'],
            'available_periods' => [1, 3, 5, 7],
        ]);

        Livewire::test(ViewTeacher::class, ['record' => $teacher->id])
            ->assertSuccessful()
            ->assertSee('Teacher Availability')
            ->assertSee('Monday')
            ->assertSee('Wednesday')
            ->assertSee('Friday')
            ->assertSee('Period 1')
            ->assertSee('Period 3')
            ->assertSee('Period 5')
            ->assertSee('Period 7');
    }

    public function test_teacher_view_action_available_in_table(): void
    {
        $teacher = Teacher::factory()->create();

        Livewire::test(ListTeachers::class)
            ->assertTableActionExists('view');
    }
}
