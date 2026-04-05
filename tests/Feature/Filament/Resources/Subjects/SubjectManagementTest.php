<?php

namespace Tests\Feature\Filament\Resources\Subjects;

use App\Filament\Resources\SubjectResource\Pages\CreateSubject;
use App\Filament\Resources\SubjectResource\Pages\EditSubject;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = [
            'subject.list',
            'subject.view',
            'subject.create',
            'subject.edit',
            'subject.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_add_subject_and_assign_to_specific_class_with_periods(): void
    {
        $classRoom = ClassRoom::factory()->create([
            'name' => 'Class 6',
            'section' => 'A',
        ]);

        Livewire::test(CreateSubject::class)
            ->fillForm([
                'name' => 'Mathematics',
                'code' => 'MATH-6A',
                'class_room_id' => $classRoom->id,
                'type' => 'core',
                'status' => 'active',
                'min_periods_per_week' => 3,
                'weekly_periods' => 5,
                'max_periods_per_week' => 6,
                'single_combined' => 'single',
                'setting_is_active' => '1',
                'priority' => 7,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $subject = Subject::query()->where('code', 'MATH-6A')->first();

        $this->assertNotNull($subject);
        $this->assertSame($classRoom->id, $subject->class_room_id);

        $this->assertDatabaseHas('class_subject_settings', [
            'class_room_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 3,
            'weekly_periods' => 5,
            'max_periods_per_week' => 6,
            'single_combined' => 'single',
            'is_active' => 1,
            'priority' => 7,
        ]);
    }

    public function test_can_edit_subject_periods_and_class_assignment(): void
    {
        $oldClassRoom = ClassRoom::factory()->create([
            'name' => 'Class 7',
            'section' => 'A',
        ]);

        $newClassRoom = ClassRoom::factory()->create([
            'name' => 'Class 7',
            'section' => 'B',
        ]);

        $subject = Subject::factory()->create([
            'name' => 'Science',
            'code' => 'SCI-7A',
            'class_room_id' => $oldClassRoom->id,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $oldClassRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 2,
            'weekly_periods' => 4,
            'max_periods_per_week' => 5,
            'single_combined' => 'single',
            'is_active' => true,
            'priority' => 5,
        ]);

        Livewire::test(EditSubject::class, ['record' => $subject->id])
            ->fillForm([
                'name' => 'Science',
                'code' => 'SCI-7A',
                'class_room_id' => $newClassRoom->id,
                'type' => 'core',
                'status' => 'active',
                'min_periods_per_week' => 1,
                'weekly_periods' => 6,
                'max_periods_per_week' => 7,
                'single_combined' => 'combined',
                'setting_is_active' => '1',
                'priority' => 9,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $subject->refresh();

        $this->assertSame($newClassRoom->id, $subject->class_room_id);

        $this->assertDatabaseHas('class_subject_settings', [
            'class_room_id' => $newClassRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 1,
            'weekly_periods' => 6,
            'max_periods_per_week' => 7,
            'single_combined' => 'combined',
            'is_active' => 1,
            'priority' => 9,
        ]);

        $this->assertDatabaseMissing('class_subject_settings', [
            'class_room_id' => $oldClassRoom->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_can_assign_special_room_for_subject_setting(): void
    {
        $classRoom = ClassRoom::factory()->create([
            'name' => 'Class 8',
            'section' => 'B',
        ]);

        $room = Room::factory()->create([
            'name' => 'Computer Lab B',
            'code' => 'LAB-COMP-B',
            'type' => 'computer_lab',
        ]);

        Livewire::test(CreateSubject::class)
            ->fillForm([
                'name' => 'Computer Science',
                'code' => 'COMP-8B',
                'class_room_id' => $classRoom->id,
                'type' => 'core',
                'status' => 'active',
                'min_periods_per_week' => 2,
                'weekly_periods' => 4,
                'max_periods_per_week' => 5,
                'single_combined' => 'single',
                'room_id' => $room->id,
                'setting_is_active' => '1',
                'priority' => 6,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $subject = Subject::query()->where('code', 'COMP-8B')->first();

        $this->assertNotNull($subject);

        $this->assertDatabaseHas('class_subject_settings', [
            'class_room_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
        ]);
    }
}
