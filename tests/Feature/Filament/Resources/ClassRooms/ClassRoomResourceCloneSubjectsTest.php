<?php

namespace Tests\Feature\Filament\Resources\ClassRooms;

use App\Filament\Resources\ClassRooms\Pages\CreateClassRoom;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClassRoomResourceCloneSubjectsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
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

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_clone_subjects_from_existing_class_when_creating_new_class(): void
    {
        $sourceClass = ClassRoom::factory()->create([
            'name' => 'Class 10',
            'section' => 'A',
            'weekly_periods' => 30,
            'total_subjects' => 5,
            'status' => 'active',
        ]);

        $math = Subject::factory()->create([
            'name' => 'Mathematics',
            'code' => 'MATH-10A',
            'class_room_id' => $sourceClass->id,
            'status' => 'active',
        ]);

        $science = Subject::factory()->create([
            'name' => 'Science',
            'code' => 'SCI-10A',
            'class_room_id' => $sourceClass->id,
            'status' => 'active',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $sourceClass->id,
            'subject_id' => $math->id,
            'weekly_periods' => 6,
            'min_periods_per_week' => 4,
            'max_periods_per_week' => 7,
            'is_active' => true,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $sourceClass->id,
            'subject_id' => $science->id,
            'weekly_periods' => 5,
            'min_periods_per_week' => 3,
            'max_periods_per_week' => 6,
            'is_active' => true,
        ]);

        Livewire::test(CreateClassRoom::class)
            ->fillForm([
                'name' => 'Class 11',
                'section' => 'A',
                'weekly_periods' => 30,
                'total_subjects' => 5,
                'status' => 'active',
                'copy_from_class_room_id' => $sourceClass->id,
                'copy_subjects_from_source' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $targetClass = ClassRoom::where('name', 'Class 11')->where('section', 'A')->first();
        $this->assertNotNull($targetClass);

        $clonedSubjects = Subject::where('class_room_id', $targetClass->id)->get();
        $this->assertCount(2, $clonedSubjects);

        $this->assertTrue($clonedSubjects->contains(fn (Subject $subject): bool => $subject->name === 'Mathematics'));
        $this->assertTrue($clonedSubjects->contains(fn (Subject $subject): bool => $subject->name === 'Science'));

        foreach ($clonedSubjects as $subject) {
            $this->assertStringEndsWith('11A', $subject->code);
        }

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'class_room_id' => $targetClass->id,
        ]);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Science',
            'class_room_id' => $targetClass->id,
        ]);
    }
}
