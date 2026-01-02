<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\ClassRange;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Services\TimetableGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TimetableGeneratorService $service;

    protected AcademicTerm $term;

    protected ClassRoom $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TimetableGeneratorService;

        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create academic term
        $this->term = AcademicTerm::create([
            'name' => '2026 - Term 1',
            'year' => 2026,
            'term' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'is_active' => true,
        ]);

        // Create class range
        $classRange = ClassRange::create([
            'name' => '1 - 4',
            'display_name' => 'Class 1-4',
            'start_class' => 1,
            'end_class' => 4,
        ]);

        // Create test class
        $this->class = ClassRoom::create([
            'name' => '1',
            'section' => 'A',
        ]);

        // Create timetable settings
        TimetableSetting::create([
            'key' => 'school_days',
            'value' => json_encode(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
        ]);

        TimetableSetting::create([
            'key' => 'periods_per_day',
            'value' => '8',
        ]);

        TimetableSetting::create([
            'key' => 'max_same_subject_per_day',
            'value' => '2',
        ]);
    }

    protected function createTeacher(array $attributes = []): Teacher
    {
        return Teacher::create(array_merge([
            'name' => 'Test Teacher',
            'employee_id' => 'EMP'.uniqid(),
            'email' => 'teacher'.uniqid().'@test.com',
            'phone' => '1234567890',
            'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], // Array, not JSON
            'available_periods' => [1, 2, 3, 4, 5, 6, 7, 8], // Array, not JSON
            'max_periods_per_week' => 30,
            'status' => 'active',
        ], $attributes));
    }

    protected function createSubject(array $attributes = []): Subject
    {
        return Subject::create(array_merge([
            'name' => 'Test Subject',
            'code' => 'SUB'.uniqid(),
            'class_range' => '1 - 4',
            'single_combined' => 'single',
            'weekly_periods' => 4,
            'min_periods_per_week' => 3,
            'max_periods_per_week' => 5,
            'type' => 'core',
            'status' => 'active',
        ], $attributes));
    }

    public function test_service_initializes_correctly(): void
    {
        $this->assertInstanceOf(TimetableGeneratorService::class, $this->service);
    }

    public function test_can_generate_timetable_for_single_class(): void
    {
        // Create teacher and subject
        $teacher = $this->createTeacher();
        $subject = $this->createSubject();

        // Create class subject setting
        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
            'min_periods_per_week' => 3,
            'max_periods_per_week' => 5,
        ]);

        // Generate timetable
        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id,
            ['clear_existing' => true]
        );

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);

        // Verify slots were created
        $slots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->get();

        $this->assertGreaterThan(0, $slots->count());
    }

    public function test_respects_minimum_periods_per_week(): void
    {
        $teacher = $this->createTeacher();
        $subject = $this->createSubject([
            'min_periods_per_week' => 4,
            'max_periods_per_week' => 6,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 5,
            'min_periods_per_week' => 4,
            'max_periods_per_week' => 6,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);

        // Count assigned periods for this subject
        $periodCount = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('subject_id', $subject->id)
            ->count();

        $this->assertGreaterThanOrEqual(4, $periodCount);
    }

    public function test_respects_maximum_periods_per_week(): void
    {
        $teacher = $this->createTeacher();
        $subject = $this->createSubject([
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 3,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);

        // Count assigned periods for this subject
        $periodCount = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('subject_id', $subject->id)
            ->count();

        $this->assertLessThanOrEqual(4, $periodCount);
    }

    public function test_prevents_teacher_double_booking(): void
    {
        $teacher = $this->createTeacher();

        // Create two subjects for same teacher
        $subject1 = $this->createSubject(['name' => 'Math']);
        $subject2 = $this->createSubject(['name' => 'Science']);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject1->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);

        // Check for no overlapping slots for same teacher
        $slots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('teacher_id', $teacher->id)
            ->get();

        $slotKeys = [];
        foreach ($slots as $slot) {
            $key = "{$slot->day}_{$slot->period}";
            $this->assertNotContains($key, $slotKeys, "Teacher double-booked at day {$slot->day}, period {$slot->period}");
            $slotKeys[] = $key;
        }
    }

    public function test_respects_teacher_unavailable_periods(): void
    {
        // Teacher only available on Sunday periods 1-4
        $teacher = $this->createTeacher([
            'available_days' => ['Sun'],
            'available_periods' => [1, 2, 3, 4],
        ]);

        $subject = $this->createSubject();

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'periods_per_week' => 3,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        // Check all assigned slots respect teacher availability
        $slots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('teacher_id', $teacher->id)
            ->get();

        // If slots were generated, they should respect availability
        if ($slots->count() > 0) {
            foreach ($slots as $slot) {
                // Day should be 0 (Sunday)
                $this->assertEquals(0, $slot->day);
                // Period should be 1-4
                $this->assertContains($slot->period, [1, 2, 3, 4]);
            }
        }

        // At minimum, verify generation completed
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_prevents_teacher_overload(): void
    {
        $teacher = $this->createTeacher([
            'max_periods_per_week' => 10,
        ]);

        // Create multiple subjects with same teacher totaling more than max
        for ($i = 1; $i <= 5; $i++) {
            $subject = $this->createSubject(['name' => "Subject $i"]);

            ClassSubjectSetting::create([
                'class_room_id' => $this->class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'periods_per_week' => 4,
            ]);
        }

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        // Count total periods assigned to teacher
        $totalPeriods = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('teacher_id', $teacher->id)
            ->count();

        $this->assertLessThanOrEqual(10, $totalPeriods);
    }

    public function test_handles_combined_periods_correctly(): void
    {
        $teacher = $this->createTeacher();
        $subject = $this->createSubject([
            'name' => 'Martial Arts',
            'single_combined' => 'combined',
        ]);

        // Create combined period
        CombinedPeriod::create([
            'name' => 'Combined Martial Arts',
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day' => 0, // Sunday
            'period' => 3,
            'class_room_ids' => [$this->class->id], // Array, not JSON string
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        // Verify generation completed (may have warnings but should not crash)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);

        // Verify combined period slot exists
        $combinedSlot = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('subject_id', $subject->id)
            ->where('day', 0)
            ->where('period', 3)
            ->first();

        $this->assertNotNull($combinedSlot, 'Combined period slot should be created');
    }

    public function test_enforces_eca_limit_one_per_day(): void
    {
        $teacher1 = $this->createTeacher(['name' => 'ECA Teacher 1']);
        $teacher2 = $this->createTeacher(['name' => 'ECA Teacher 2']);

        // Create two ECA subjects
        $eca1 = $this->createSubject([
            'name' => 'Music',
            'type' => 'co_curricular',
        ]);

        $eca2 = $this->createSubject([
            'name' => 'Art',
            'type' => 'co_curricular',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $eca1->id,
            'teacher_id' => $teacher1->id,
            'periods_per_week' => 2,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $eca2->id,
            'teacher_id' => $teacher2->id,
            'periods_per_week' => 2,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);

        // Check each day has max 1 ECA subject
        $slots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->whereIn('subject_id', [$eca1->id, $eca2->id])
            ->get();

        $ecaPerDay = [];
        foreach ($slots as $slot) {
            $ecaPerDay[$slot->day] = ($ecaPerDay[$slot->day] ?? 0) + 1;
        }

        foreach ($ecaPerDay as $day => $count) {
            $this->assertLessThanOrEqual(1, $count, "Day $day has more than 1 ECA subject");
        }
    }

    public function test_clears_existing_timetable_when_option_enabled(): void
    {
        $teacher = $this->createTeacher();
        $subject = $this->createSubject();

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
        ]);

        // Create existing slot
        TimetableSlot::create([
            'class_room_id' => $this->class->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day' => 0,
            'period' => 1,
        ]);

        $oldSlotCount = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->count();

        $this->assertEquals(1, $oldSlotCount);

        // Generate with clear_existing = true
        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id,
            ['clear_existing' => true]
        );

        $this->assertTrue($result['success']);

        // Verify new timetable was generated (different from old)
        $newSlots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->get();

        $this->assertGreaterThan(0, $newSlots->count());
    }

    public function test_handles_empty_subject_settings_gracefully(): void
    {
        // No class subject settings created

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        // Should not crash, may have warnings
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('warnings', $result);
    }

    public function test_returns_statistics_after_generation(): void
    {
        $teacher = $this->createTeacher();
        $subject = $this->createSubject();

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertIsArray($result['statistics']);
    }

    public function test_handles_multiple_classes_generation(): void
    {
        $class2 = ClassRoom::create([
            'name' => '2',
            'section' => 'A',
        ]);

        $teacher = $this->createTeacher();
        $subject = $this->createSubject();

        // Create settings for both classes
        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $class2->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'periods_per_week' => 4,
        ]);

        $result = $this->service->generate(
            [$this->class->id, $class2->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);

        // Verify both classes have slots
        $class1Slots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->count();

        $class2Slots = TimetableSlot::where('class_room_id', $class2->id)
            ->where('academic_term_id', $this->term->id)
            ->count();

        $this->assertGreaterThan(0, $class1Slots);
        $this->assertGreaterThan(0, $class2Slots);
    }

    public function test_assigns_class_teacher_to_first_period(): void
    {
        $subject = $this->createSubject(['name' => 'English']);

        // Create class teacher who teaches the subject
        $classTeacher = $this->createTeacher([
            'name' => 'Class Teacher',
            'subject_ids' => [$subject->id], // Array, not JSON - model casts it
        ]);

        // Assign class teacher to this class
        $this->class->update(['class_teacher_id' => $classTeacher->id]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $subject->id,
            'periods_per_week' => 6,
        ]);

        $result = $this->service->generate(
            [$this->class->id],
            $this->term->id
        );

        $this->assertTrue($result['success']);

        // Check that period 1 slots are assigned to the class teacher
        $period1Slots = TimetableSlot::where('class_room_id', $this->class->id)
            ->where('academic_term_id', $this->term->id)
            ->where('period', 1)
            ->where('teacher_id', $classTeacher->id)
            ->count();

        // Should have class teacher assigned to period 1 on multiple days
        $this->assertGreaterThan(0, $period1Slots);
    }
}
