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
            'availability_matrix' => [
                'Mon' => [1 => true, 2 => true, 3 => true, 4 => true],
                'Tue' => [1 => true, 2 => true, 3 => true, 4 => true],
                'Wed' => [1 => true, 2 => true, 3 => true, 4 => true],
            ],
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

        // Verify availability_matrix has the correct days and periods
        $matrix = $teacher->availability_matrix;
        $this->assertIsArray($matrix);
        $this->assertArrayHasKey('Mon', $matrix);
        $this->assertArrayHasKey('Tue', $matrix);
        $this->assertArrayHasKey('Wed', $matrix);
        $this->assertArrayHasKey('Thu', $matrix);
        $this->assertArrayHasKey('Fri', $matrix);

        // Check that periods 1-6 are set for Monday
        foreach ([1, 2, 3, 4, 5, 6] as $period) {
            $this->assertTrue($matrix['Mon'][$period] ?? false);
        }
    }

    public function test_can_edit_teacher_with_availability_grid(): void
    {
        $subject = Subject::factory()->create();

        $teacher = Teacher::factory()->create([
            'subject_ids' => [$subject->id],
            'availability_matrix' => [
                'Mon' => [1 => true, 2 => true],
                'Tue' => [1 => true, 2 => true],
            ],
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

        // Verify availability_matrix was updated
        $matrix = $teacher->availability_matrix;
        $this->assertIsArray($matrix);
        $this->assertArrayHasKey('Mon', $matrix);
        $this->assertArrayHasKey('Tue', $matrix);
        $this->assertArrayHasKey('Wed', $matrix);
        $this->assertArrayHasKey('Thu', $matrix);
        $this->assertArrayHasKey('Fri', $matrix);

        // Check that all 8 periods are set for Monday
        foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $period) {
            $this->assertTrue($matrix['Mon'][$period] ?? false);
        }
    }

    public function test_availability_grid_displays_correctly_in_view(): void
    {
        $teacher = Teacher::factory()->create([
            'name' => 'Test Teacher',
            'availability_matrix' => [
                'Mon' => [1 => true, 3 => true, 5 => true, 7 => true],
                'Wed' => [1 => true, 3 => true, 5 => true, 7 => true],
                'Fri' => [1 => true, 3 => true, 5 => true, 7 => true],
            ],
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

    public function test_can_filter_teachers_by_status(): void
    {
        $activeTeacher = Teacher::factory()->create([
            'name' => 'Active Teacher',
            'status' => 'active',
        ]);

        $inactiveTeacher = Teacher::factory()->create([
            'name' => 'Inactive Teacher',
            'status' => 'inactive',
        ]);

        // Filter by active status
        Livewire::test(ListTeachers::class)
            ->filterTable('status', 'active')
            ->assertCanSeeTableRecords([$activeTeacher])
            ->assertCanNotSeeTableRecords([$inactiveTeacher]);

        // Filter by inactive status
        Livewire::test(ListTeachers::class)
            ->filterTable('status', 'inactive')
            ->assertCanSeeTableRecords([$inactiveTeacher])
            ->assertCanNotSeeTableRecords([$activeTeacher]);
    }

    public function test_can_filter_teachers_by_subject(): void
    {
        $mathSubject = Subject::factory()->create(['name' => 'Mathematics']);
        $scienceSubject = Subject::factory()->create(['name' => 'Science']);

        $mathTeacher = Teacher::factory()->create([
            'name' => 'Math Teacher',
            'subject_ids' => [$mathSubject->id],
        ]);

        $scienceTeacher = Teacher::factory()->create([
            'name' => 'Science Teacher',
            'subject_ids' => [$scienceSubject->id],
        ]);

        $bothTeacher = Teacher::factory()->create([
            'name' => 'Both Subjects Teacher',
            'subject_ids' => [$mathSubject->id, $scienceSubject->id],
        ]);

        // Filter by Math subject
        Livewire::test(ListTeachers::class)
            ->filterTable('subject_ids', $mathSubject->id)
            ->assertCanSeeTableRecords([$mathTeacher, $bothTeacher])
            ->assertCanNotSeeTableRecords([$scienceTeacher]);

        // Filter by Science subject
        Livewire::test(ListTeachers::class)
            ->filterTable('subject_ids', $scienceSubject->id)
            ->assertCanSeeTableRecords([$scienceTeacher, $bothTeacher])
            ->assertCanNotSeeTableRecords([$mathTeacher]);
    }

    public function test_can_filter_teachers_by_assigned_class(): void
    {
        $class10A = ClassRoom::factory()->create(['name' => 'Class 10', 'section' => 'A']);
        $class10B = ClassRoom::factory()->create(['name' => 'Class 10', 'section' => 'B']);

        $teacher10A = Teacher::factory()->create([
            'name' => 'Teacher for 10A',
            'class_room_ids' => [$class10A->id],
        ]);

        $teacher10B = Teacher::factory()->create([
            'name' => 'Teacher for 10B',
            'class_room_ids' => [$class10B->id],
        ]);

        $teacherBoth = Teacher::factory()->create([
            'name' => 'Teacher for Both',
            'class_room_ids' => [$class10A->id, $class10B->id],
        ]);

        $teacherAllClasses = Teacher::factory()->create([
            'name' => 'Teacher for All Classes',
            'class_room_ids' => null,
        ]);

        // Filter by Class 10-A
        Livewire::test(ListTeachers::class)
            ->filterTable('class_room_ids', $class10A->id)
            ->assertCanSeeTableRecords([$teacher10A, $teacherBoth])
            ->assertCanNotSeeTableRecords([$teacher10B, $teacherAllClasses]);

        // Filter by Class 10-B
        Livewire::test(ListTeachers::class)
            ->filterTable('class_room_ids', $class10B->id)
            ->assertCanSeeTableRecords([$teacher10B, $teacherBoth])
            ->assertCanNotSeeTableRecords([$teacher10A, $teacherAllClasses]);
    }

    public function test_can_filter_teachers_by_availability(): void
    {
        $fullWeekTeacher = Teacher::factory()->create([
            'name' => 'Full Week Teacher',
            'availability_matrix' => [
                'Mon' => [1 => true, 2 => true],
                'Tue' => [1 => true, 2 => true],
                'Wed' => [1 => true, 2 => true],
                'Thu' => [1 => true, 2 => true],
                'Fri' => [1 => true, 2 => true],
                'Sat' => [1 => true, 2 => true],
            ],
        ]);

        $partialTeacher = Teacher::factory()->create([
            'name' => 'Partial Availability Teacher',
            'availability_matrix' => [
                'Mon' => [1 => true, 2 => true],
                'Tue' => [1 => true, 2 => true],
                'Wed' => [1 => true, 2 => true],
            ],
        ]);

        // Filter by full week availability
        Livewire::test(ListTeachers::class)
            ->filterTable('availability', 'full_week')
            ->assertCanSeeTableRecords([$fullWeekTeacher])
            ->assertCanNotSeeTableRecords([$partialTeacher]);

        // Filter by partial availability
        Livewire::test(ListTeachers::class)
            ->filterTable('availability', 'partial')
            ->assertCanSeeTableRecords([$partialTeacher])
            ->assertCanNotSeeTableRecords([$fullWeekTeacher]);
    }

    public function test_can_combine_multiple_filters(): void
    {
        $mathSubject = Subject::factory()->create(['name' => 'Mathematics']);
        $scienceSubject = Subject::factory()->create(['name' => 'Science']);
        $class10A = ClassRoom::factory()->create(['name' => 'Class 10', 'section' => 'A']);

        $targetTeacher = Teacher::factory()->create([
            'name' => 'Target Teacher',
            'status' => 'active',
            'subject_ids' => [$mathSubject->id],
            'class_room_ids' => [$class10A->id],
        ]);

        $wrongStatusTeacher = Teacher::factory()->create([
            'name' => 'Wrong Status Teacher',
            'status' => 'inactive',
            'subject_ids' => [$mathSubject->id],
            'class_room_ids' => [$class10A->id],
        ]);

        $wrongSubjectTeacher = Teacher::factory()->create([
            'name' => 'Wrong Subject Teacher',
            'status' => 'active',
            'subject_ids' => [$scienceSubject->id],
            'class_room_ids' => [$class10A->id],
        ]);

        // Apply multiple filters
        Livewire::test(ListTeachers::class)
            ->filterTable('status', 'active')
            ->filterTable('subject_ids', $mathSubject->id)
            ->filterTable('class_room_ids', $class10A->id)
            ->assertCanSeeTableRecords([$targetTeacher])
            ->assertCanNotSeeTableRecords([$wrongStatusTeacher, $wrongSubjectTeacher]);
    }
}
