<?php

namespace Tests\Feature\Filament\Resources\Rooms;

use App\Filament\Resources\RoomResource\Pages\CreateRoom;
use App\Filament\Resources\RoomResource\Pages\EditRoom;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = [
            'room.list',
            'room.view',
            'room.create',
            'room.edit',
            'room.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_add_classroom_and_labs(): void
    {
        Livewire::test(CreateRoom::class)
            ->fillForm([
                'name' => 'Computer Lab A',
                'code' => 'LAB-COMP-A',
                'type' => 'computer_lab',
                'capacity' => 30,
                'status' => 'active',
                'notes' => 'For coding and practical classes',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rooms', [
            'name' => 'Computer Lab A',
            'code' => 'LAB-COMP-A',
            'type' => 'computer_lab',
            'capacity' => 30,
            'status' => 'active',
        ]);
    }

    public function test_can_edit_room_configuration(): void
    {
        $room = Room::factory()->create([
            'name' => 'Science Lab 1',
            'code' => 'LAB-SCI-1',
            'type' => 'science_lab',
            'capacity' => 25,
            'status' => 'active',
        ]);

        Livewire::test(EditRoom::class, ['record' => $room->id])
            ->fillForm([
                'name' => 'Science Lab 1 - Renovated',
                'code' => 'LAB-SCI-1',
                'type' => 'science_lab',
                'capacity' => 35,
                'status' => 'active',
                'notes' => 'New benches installed',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Science Lab 1 - Renovated',
            'capacity' => 35,
        ]);
    }
}
