<?php

namespace Tests\Feature\Filament\Resources\ClassSubjectSettings;

use App\Filament\Resources\ClassSubjectSettingResource\Pages\CreateClassSubjectSetting;
use App\Models\ClassRoom;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClassSubjectSettingRoomAssignmentTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = [
            'class_subject_setting.list',
            'class_subject_setting.view',
            'class_subject_setting.create',
            'class_subject_setting.edit',
            'class_subject_setting.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_assign_special_room_to_class_subject_setting(): void
    {
        $classRoom = ClassRoom::factory()->create([
            'name' => 'Class 8',
            'section' => 'A',
        ]);

        $subject = Subject::factory()->create([
            'name' => 'Computer Science',
            'code' => 'COMP-8A',
            'class_room_id' => $classRoom->id,
            'type' => 'core',
            'status' => 'active',
        ]);

        $computerLab = Room::factory()->create([
            'name' => 'Computer Lab Main',
            'code' => 'LAB-COMP-MAIN',
            'type' => 'computer_lab',
            'status' => 'active',
        ]);

        Livewire::test(CreateClassSubjectSetting::class)
            ->fillForm([
                'class_room_id' => $classRoom->id,
                'subject_id' => $subject->id,
                'room_id' => $computerLab->id,
                'min_periods_per_week' => 2,
                'weekly_periods' => 4,
                'max_periods_per_week' => 5,
                'single_combined' => 'single',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('class_subject_settings', [
            'class_room_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'room_id' => $computerLab->id,
        ]);
    }
}
