<?php

namespace Tests\Feature\Filament\Resources\ClassRooms;

use App\Filament\Resources\ClassRooms\Pages\CreateClassRoom;
use App\Filament\Resources\ClassRooms\Pages\EditClassRoom;
use App\Models\ClassRoom;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClassRoomManagementTest extends TestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();

        $permissions = [
            'class_room.list',
            'class_room.view',
            'class_room.create',
            'class_room.edit',
            'class_room.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->adminUser->givePermissionTo($permissions);
        $this->actingAs($this->adminUser);
    }

    public function test_can_create_class_with_custom_section_and_capacity(): void
    {
        Livewire::test(CreateClassRoom::class)
            ->fillForm([
                'name' => 'Class 7',
                'section' => 'E',
                'capacity' => 45,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('class_rooms', [
            'name' => 'Class 7',
            'section' => 'E',
            'capacity' => 45,
        ]);
    }

    public function test_can_assign_students_to_class_within_capacity(): void
    {
        $studentOne = User::factory()->create();
        $studentTwo = User::factory()->create();

        Livewire::test(CreateClassRoom::class)
            ->fillForm([
                'name' => 'Class 8',
                'section' => 'A',
                'capacity' => 2,
                'status' => 'active',
                'student_ids' => [$studentOne->id, $studentTwo->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $classRoom = ClassRoom::query()
            ->where('name', 'Class 8')
            ->where('section', 'A')
            ->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $studentOne->id,
            'class_room_id' => $classRoom->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $studentTwo->id,
            'class_room_id' => $classRoom->id,
        ]);
    }

    public function test_cannot_assign_students_beyond_capacity(): void
    {
        $students = User::factory()->count(3)->create();

        Livewire::test(CreateClassRoom::class)
            ->fillForm([
                'name' => 'Class 9',
                'section' => 'B',
                'capacity' => 2,
                'status' => 'active',
                'student_ids' => $students->pluck('id')->all(),
            ])
            ->call('create')
            ->assertHasErrors(['student_ids']);

        $this->assertDatabaseMissing('class_rooms', [
            'name' => 'Class 9',
            'section' => 'B',
        ]);
    }

    public function test_can_edit_class_and_reassign_students(): void
    {
        $classRoom = ClassRoom::factory()->create([
            'name' => 'Class 10',
            'section' => 'C',
            'capacity' => 3,
        ]);

        $currentStudent = User::factory()->create([
            'class_room_id' => $classRoom->id,
        ]);

        $newStudent = User::factory()->create();

        Livewire::test(EditClassRoom::class, ['record' => $classRoom->id])
            ->fillForm([
                'name' => 'Class 10',
                'section' => 'C',
                'capacity' => 3,
                'status' => 'active',
                'student_ids' => [$newStudent->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $newStudent->id,
            'class_room_id' => $classRoom->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $currentStudent->id,
            'class_room_id' => null,
        ]);
    }
}
