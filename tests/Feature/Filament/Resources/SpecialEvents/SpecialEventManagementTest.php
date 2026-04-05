<?php

namespace Tests\Feature\Filament\Resources\SpecialEvents;

use App\Filament\Resources\SpecialEventResource\Pages\CreateSpecialEvent;
use App\Filament\Resources\SpecialEventResource\Pages\EditSpecialEvent;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\SpecialEvent;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SpecialEventManagementTest extends TestCase
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
            'special_event.list',
            'special_event.view',
            'special_event.create',
            'special_event.edit',
            'special_event.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_create_special_event(): void
    {
        Livewire::test(CreateSpecialEvent::class)
            ->fillForm([
                'academic_term_id' => $this->term->id,
                'class_room_id' => null,
                'name' => 'Annual Sports Day',
                'event_type' => 'event',
                'date' => now()->addDays(14)->toDateString(),
                'day_of_week' => 5,
                'is_school_wide' => true,
                'blocks_timetable' => true,
                'description' => 'Whole school event',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('special_events', [
            'academic_term_id' => $this->term->id,
            'name' => 'Annual Sports Day',
            'blocks_timetable' => true,
            'is_school_wide' => true,
        ]);
    }

    public function test_can_edit_special_event(): void
    {
        $event = SpecialEvent::query()->create([
            'academic_term_id' => $this->term->id,
            'class_room_id' => $this->classRoom->id,
            'name' => 'Orientation Program',
            'event_type' => 'program',
            'date' => now()->addDays(5)->toDateString(),
            'day_of_week' => 3,
            'is_school_wide' => false,
            'blocks_timetable' => false,
        ]);

        Livewire::test(EditSpecialEvent::class, ['record' => $event->id])
            ->fillForm([
                'academic_term_id' => $this->term->id,
                'class_room_id' => $this->classRoom->id,
                'name' => 'Orientation + Cultural Program',
                'event_type' => 'program',
                'date' => now()->addDays(8)->toDateString(),
                'day_of_week' => 4,
                'is_school_wide' => false,
                'blocks_timetable' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('special_events', [
            'id' => $event->id,
            'name' => 'Orientation + Cultural Program',
            'blocks_timetable' => true,
        ]);
    }
}
