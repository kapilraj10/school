<?php

namespace Tests\Feature\Filament\Resources\TimetableSettings;

use App\Filament\Resources\TimetableSettingResource\Pages\ListTimetableSettings;
use App\Models\TimetableSetting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TimeSlotManagementTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = [
            'timetable_setting.list',
            'timetable_setting.view',
            'timetable_setting.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_can_save_time_slot_management_settings(): void
    {
        Livewire::test(ListTimetableSettings::class)
            ->callTableAction('configure_time_slots', data: [
                'school_days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'periods_per_day' => 8,
                'period_duration_minutes' => 45,
                'school_start_time' => '09:00',
                'short_break_after_period' => '3',
                'short_break_duration_minutes' => 15,
                'lunch_break_after_period' => '5',
                'lunch_break_duration_minutes' => 30,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(8, TimetableSetting::get('periods_per_day'));
        $this->assertSame(45, TimetableSetting::get('period_duration_minutes'));
        $this->assertSame('09:00', TimetableSetting::get('school_start_time'));
        $this->assertSame(3, TimetableSetting::get('short_break_after_period'));
        $this->assertSame(15, TimetableSetting::get('short_break_duration_minutes'));
        $this->assertSame(5, TimetableSetting::get('lunch_break_after_period'));
        $this->assertSame(30, TimetableSetting::get('lunch_break_duration_minutes'));
        $this->assertSame(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], TimetableSetting::get('school_days'));
    }

    public function test_lunch_and_short_break_cannot_be_same_period(): void
    {
        Livewire::test(ListTimetableSettings::class)
            ->callTableAction('configure_time_slots', data: [
                'school_days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'periods_per_day' => 8,
                'period_duration_minutes' => 45,
                'school_start_time' => '09:00',
                'short_break_after_period' => '4',
                'short_break_duration_minutes' => 15,
                'lunch_break_after_period' => '4',
                'lunch_break_duration_minutes' => 30,
            ])
            ->assertHasTableActionErrors();
    }
}
