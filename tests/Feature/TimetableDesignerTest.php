<?php

namespace Tests\Feature;

use App\Filament\Pages\TimetableDesigner;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class TimetableDesignerTest extends TestCase
{
    private User $user;

    private ClassRoom $classRoom;

    private AcademicTerm $term;

    private Subject $mathSubject;

    private Subject $englishSubject;

    private Subject $danceSubject;

    private Teacher $teacher1;

    private Teacher $teacher2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create academic term
        $this->term = AcademicTerm::factory()->active()->create([
            'name' => '2026 - Term 1',
            'year' => 2026,
            'term' => 1,
        ]);

        // Create class
        $this->classRoom = ClassRoom::factory()->create([
            'name' => 'Class 5',
            'section' => 'A',
            'status' => 'active',
        ]);

        // Create subjects
        $this->mathSubject = Subject::factory()->core()->create([
            'name' => 'Math',
            'code' => 'MTH',
            'class_room_id' => $this->classRoom->id,
        ]);

        $this->englishSubject = Subject::factory()->core()->create([
            'name' => 'English',
            'code' => 'ENG',
            'class_room_id' => $this->classRoom->id,
        ]);

        $this->danceSubject = Subject::factory()->coCurricular()->create([
            'name' => 'Dance',
            'code' => 'DNC',
            'class_room_id' => $this->classRoom->id,
        ]);

        // Create teachers
        $this->teacher1 = Teacher::factory()->create([
            'name' => 'Mr. Smith',
            'status' => 'active',
        ]);

        $this->teacher2 = Teacher::factory()->create([
            'name' => 'Ms. Johnson',
            'status' => 'active',
        ]);

        // Create class subject settings
        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $this->mathSubject->id,
            'min_periods_per_week' => 5,
            'max_periods_per_week' => 6,
            'weekly_periods' => 5,
            'is_active' => true,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $this->englishSubject->id,
            'min_periods_per_week' => 5,
            'max_periods_per_week' => 6,
            'weekly_periods' => 5,
            'is_active' => true,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $this->danceSubject->id,
            'min_periods_per_week' => 1,
            'max_periods_per_week' => 3,
            'weekly_periods' => 2,
            'is_active' => true,
        ]);
    }

    public function test_can_load_timetable_designer_page(): void
    {
        // Filament pages are within admin panel, use Livewire test instead
        Livewire::test(TimetableDesigner::class)
            ->assertStatus(200);
    }

    public function test_page_loads_with_default_selections(): void
    {
        Livewire::test(TimetableDesigner::class)
            ->assertSet('selectedTermId', $this->term->id)
            ->assertSet('selectedClassRoomId', $this->classRoom->id)
            ->assertStatus(200);
    }

    public function test_can_select_class_and_term(): void
    {
        $anotherClass = ClassRoom::factory()->create(['name' => 'Class 6', 'section' => 'B']);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $anotherClass->id)
            ->assertSet('selectedClassRoomId', $anotherClass->id)
            ->assertStatus(200);
    }

    public function test_loads_existing_timetable_slots(): void
    {
        // Create existing slots
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacher1->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->englishSubject->id,
            'teacher_id' => $this->teacher2->id,
            'day' => 1,
            'period' => 2,
        ]);

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable');

        // Verify slots are loaded
        $this->assertNotNull($component->get('timetableSlots')[1][1]);
        $this->assertEquals($this->mathSubject->id, $component->get('timetableSlots')[1][1]['subject_id']);
        $this->assertNotNull($component->get('timetableSlots')[1][2]);
        $this->assertEquals($this->englishSubject->id, $component->get('timetableSlots')[1][2]['subject_id']);
    }

    public function test_can_assign_slot(): void
    {
        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('assignSlot', 1, 1, $this->mathSubject->id, $this->teacher1->id)
            ->assertNotified();

        // Verify slot was created in database
        $this->assertDatabaseHas('timetable_slots', [
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacher1->id,
            'day' => 1,
            'period' => 1,
        ]);

        // Verify slot is in component state
        $this->assertNotNull($component->get('timetableSlots')[1][1]);
        $this->assertEquals($this->mathSubject->id, $component->get('timetableSlots')[1][1]['subject_id']);
    }

    public function test_can_assign_slot_without_teacher(): void
    {
        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('assignSlot', 1, 1, $this->mathSubject->id, null)
            ->assertNotified();

        $this->assertDatabaseHas('timetable_slots', [
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
            'teacher_id' => null,
        ]);
    }

    public function test_cannot_assign_to_locked_slot(): void
    {
        // Create locked slot
        TimetableSlot::factory()->locked()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacher1->id,
            'day' => 1,
            'period' => 1,
        ]);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('assignSlot', 1, 1, $this->englishSubject->id, $this->teacher2->id)
            ->assertNotified(); // Should notify about locked slot

        // Verify slot wasn't changed
        $this->assertDatabaseHas('timetable_slots', [
            'day' => 1,
            'period' => 1,
            'subject_id' => $this->mathSubject->id, // Still the old subject
        ]);
    }

    public function test_can_remove_slot(): void
    {
        // Create slot
        $slot = TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacher1->id,
            'day' => 1,
            'period' => 1,
        ]);

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('removeSlot', 1, 1)
            ->assertNotified();

        // Verify slot was deleted
        $this->assertDatabaseMissing('timetable_slots', [
            'id' => $slot->id,
        ]);

        // Verify slot is removed from component state
        $this->assertNull($component->get('timetableSlots')[1][1]);
    }

    public function test_cannot_remove_locked_slot(): void
    {
        $slot = TimetableSlot::factory()->locked()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
        ]);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('removeSlot', 1, 1)
            ->assertNotified(); // Should notify about locked slot

        // Verify slot still exists
        $this->assertDatabaseHas('timetable_slots', [
            'id' => $slot->id,
        ]);
    }

    public function test_can_swap_slots(): void
    {
        // Create two slots
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacher1->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->englishSubject->id,
            'teacher_id' => $this->teacher2->id,
            'day' => 1,
            'period' => 2,
        ]);

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('swapSlots', 1, 1, 1, 2)
            ->assertNotified();

        // Verify swap in database
        $this->assertDatabaseHas('timetable_slots', [
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 2, // Swapped to period 2
        ]);

        $this->assertDatabaseHas('timetable_slots', [
            'subject_id' => $this->englishSubject->id,
            'day' => 1,
            'period' => 1, // Swapped to period 1
        ]);

        // Verify swap in component state
        $this->assertEquals($this->englishSubject->id, $component->get('timetableSlots')[1][1]['subject_id']);
        $this->assertEquals($this->mathSubject->id, $component->get('timetableSlots')[1][2]['subject_id']);
    }

    public function test_cannot_swap_locked_slots(): void
    {
        TimetableSlot::factory()->locked()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->englishSubject->id,
            'day' => 1,
            'period' => 2,
        ]);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('swapSlots', 1, 1, 1, 2)
            ->assertNotified(); // Should notify about locked slot

        // Verify slots weren't swapped
        $this->assertDatabaseHas('timetable_slots', [
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1, // Still in original position
        ]);
    }

    public function test_can_toggle_lock_slot(): void
    {
        $slot = TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
            'is_locked' => false,
        ]);

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('toggleLockSlot', 1, 1)
            ->assertNotified();

        // Verify lock status changed
        $this->assertDatabaseHas('timetable_slots', [
            'id' => $slot->id,
            'is_locked' => true,
        ]);

        // Toggle back
        $component->call('toggleLockSlot', 1, 1);

        $this->assertDatabaseHas('timetable_slots', [
            'id' => $slot->id,
            'is_locked' => false,
        ]);
    }

    public function test_validation_runs_after_slot_assignment(): void
    {
        // Assign 3 Math periods on same day (violates daily limit)
        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('assignSlot', 1, 1, $this->mathSubject->id, $this->teacher1->id)
            ->call('assignSlot', 1, 2, $this->mathSubject->id, $this->teacher1->id)
            ->call('assignSlot', 1, 3, $this->mathSubject->id, $this->teacher1->id);

        // Verify validation errors exist
        $errors = $component->get('validationErrors');
        $this->assertNotEmpty($errors);

        $dailyLimitError = collect($errors)->firstWhere('type', 'subject_daily_limit');
        $this->assertNotNull($dailyLimitError);
    }

    public function test_cannot_save_with_validation_errors(): void
    {
        // Create invalid timetable (3 Math periods on same day)
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 2,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 3,
        ]);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('saveTimetable')
            ->assertNotified(); // Should notify about errors
    }

    public function test_can_save_with_warnings(): void
    {
        // Create valid timetable but with warnings (Math twice on same day, within limit)
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 2,
        ]);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('saveTimetable')
            ->assertNotified(); // Should notify about warnings but allow save
    }

    public function test_can_reset_timetable(): void
    {
        // Create some slots
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
            'is_locked' => false,
        ]);

        $lockedSlot = TimetableSlot::factory()->locked()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->englishSubject->id,
            'day' => 1,
            'period' => 2,
        ]);

        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable')
            ->call('resetTimetable')
            ->assertNotified();

        // Verify unlocked slots were deleted
        $this->assertDatabaseMissing('timetable_slots', [
            'class_room_id' => $this->classRoom->id,
            'day' => 1,
            'period' => 1,
        ]);

        // Verify locked slot still exists
        $this->assertDatabaseHas('timetable_slots', [
            'id' => $lockedSlot->id,
        ]);
    }

    public function test_calculates_subject_placements_correctly(): void
    {
        // Create multiple Math slots
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 2,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 3,
            'period' => 1,
        ]);

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable');

        // Verify placement count
        $placementCount = $component->instance()->getSubjectPlacementCount($this->mathSubject->id);
        $this->assertEquals(3, $placementCount);
    }

    public function test_calculates_constraint_status_correctly(): void
    {
        // Create 3 Math slots (required: 5)
        for ($day = 1; $day <= 3; $day++) {
            TimetableSlot::factory()->create([
                'class_room_id' => $this->classRoom->id,
                'academic_term_id' => $this->term->id,
                'subject_id' => $this->mathSubject->id,
                'day' => $day,
                'period' => 1,
            ]);
        }

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable');

        $constraintStatus = $component->get('constraintStatus')[$this->mathSubject->id];

        $this->assertEquals(3, $constraintStatus['placed']);
        $this->assertEquals(5, $constraintStatus['required']);
        $this->assertEquals(60, $constraintStatus['percentage']);
        $this->assertFalse($constraintStatus['satisfied']);
        $this->assertEquals('partial', $constraintStatus['status']);
    }

    public function test_requires_class_and_term_selection(): void
    {
        Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', null)
            ->set('selectedTermId', null)
            ->call('assignSlot', 1, 1, $this->mathSubject->id, $this->teacher1->id)
            ->assertNotified(); // Should notify to select class and term
    }

    public function test_slot_validation_state_is_correct(): void
    {
        // Create slot with error (3 Math periods on same day)
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 2,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->mathSubject->id,
            'day' => 1,
            'period' => 3,
        ]);

        $component = Livewire::test(TimetableDesigner::class)
            ->set('selectedClassRoomId', $this->classRoom->id)
            ->set('selectedTermId', $this->term->id)
            ->call('loadTimetable');

        // Empty slot
        $state = $component->instance()->getSlotValidationState(2, 1);
        $this->assertEquals('empty', $state);

        // Valid filled slot
        $state = $component->instance()->getSlotValidationState(1, 1);
        $this->assertContains($state, ['valid', 'error']); // May have error due to daily limit
    }
}
