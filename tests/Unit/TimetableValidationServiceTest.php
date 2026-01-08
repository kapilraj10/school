<?php

namespace Tests\Unit;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Services\TimetableValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimetableValidationService $validator;

    private ClassRoom $classRoom;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TimetableValidationService;

        // Create test data
        $this->classRoom = ClassRoom::factory()->create();
        $this->term = AcademicTerm::factory()->active()->create();
    }

    public function test_validates_empty_timetable_with_warnings(): void
    {
        $slots = [];
        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertFalse($result['has_errors']);
        $this->assertTrue($result['has_warnings']);
        $this->assertNotEmpty($result['warnings']);

        // Should have empty slots warning
        $warning = collect($result['warnings'])->firstWhere('type', 'empty_slots');
        $this->assertNotNull($warning);
    }

    public function test_detects_subject_daily_limit_violation(): void
    {
        $subject = Subject::factory()->core()->create(['name' => 'Math']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
                3 => ['subject_id' => $subject->id, 'subject_name' => 'Math'], // 3rd period - violation
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);
        $this->assertNotEmpty($result['errors']);

        $error = collect($result['errors'])->firstWhere('type', 'subject_daily_limit');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Max: 2 periods per day', $error['message']);
        $this->assertEquals(1, $error['day']);
        $this->assertEquals([1, 2, 3], $error['periods']);
    }

    public function test_allows_subject_at_daily_limit(): void
    {
        $subject = Subject::factory()->core()->create(['name' => 'Math']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'Math'], // Exactly 2 - OK
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $error = collect($result['errors'])->firstWhere('type', 'subject_daily_limit');
        $this->assertNull($error);
    }

    public function test_detects_cocurricular_multiple_subjects_per_day(): void
    {
        // Create co-curricular subjects
        $dance = Subject::factory()->coCurricular()->create(['name' => 'Dance', 'code' => 'DNC']);
        $sport = Subject::factory()->coCurricular()->create(['name' => 'Sport', 'code' => 'SPT']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'],
                2 => ['subject_id' => $sport->id, 'subject_name' => 'Sport'], // Different co-curricular - violation
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'multiple_cocurricular');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Only 1 allowed per day', $error['message']);
        $this->assertStringContainsString('Dance', $error['message']);
        $this->assertStringContainsString('Sport', $error['message']);
    }

    public function test_detects_cocurricular_non_consecutive_periods(): void
    {
        $dance = Subject::factory()->coCurricular()->create(['name' => 'Dance', 'code' => 'DNC']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'],
                3 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'], // Non-consecutive - violation
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'cocurricular_not_consecutive');
        $this->assertNotNull($error);
        $this->assertStringContainsString('not consecutive', $error['message']);
        $this->assertEquals([1, 3], $error['periods']);
    }

    public function test_detects_cocurricular_period_limit_exceeded(): void
    {
        $dance = Subject::factory()->coCurricular()->create(['name' => 'Dance', 'code' => 'DNC']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'],
                2 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'],
                3 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'], // 3 periods - violation
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'cocurricular_period_limit');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Max: 2 periods', $error['message']);
    }

    public function test_allows_consecutive_cocurricular_periods(): void
    {
        $dance = Subject::factory()->coCurricular()->create(['name' => 'Dance', 'code' => 'DNC']);

        $slots = [
            1 => [ // Monday
                4 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'],
                5 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'], // Consecutive - OK
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        // Should not have non-consecutive error
        $error = collect($result['errors'])->firstWhere('type', 'cocurricular_not_consecutive');
        $this->assertNull($error);

        $limitError = collect($result['errors'])->firstWhere('type', 'cocurricular_period_limit');
        $this->assertNull($limitError);
    }

    public function test_warns_about_cocurricular_in_early_periods(): void
    {
        $dance = Subject::factory()->coCurricular()->create(['name' => 'Dance', 'code' => 'DNC']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $dance->id, 'subject_name' => 'Dance'], // Period 1 - early placement
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_warnings']);

        $warning = collect($result['warnings'])->firstWhere('type', 'cocurricular_early_placement');
        $this->assertNotNull($warning);
        $this->assertStringContainsString('middle or later periods', $warning['message']);
        $this->assertEquals(1, $warning['period']);
    }

    public function test_detects_weekly_requirement_violations(): void
    {
        $subject = Subject::factory()->core()->create([
            'name' => 'English',
            'code' => 'ENG',
            'weekly_periods' => 5,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 5,
            'max_periods_per_week' => 6,
            'weekly_periods' => 5,
            'is_active' => true,
        ]);

        // Only 2 periods when 5 are required
        $slots = [
            1 => [1 => ['subject_id' => $subject->id, 'subject_name' => 'English']],
            2 => [1 => ['subject_id' => $subject->id, 'subject_name' => 'English']],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'weekly_minimum');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Min required: 5', $error['message']);
        $this->assertStringContainsString('only 2 periods', $error['message']);
    }

    public function test_detects_weekly_maximum_violations(): void
    {
        $subject = Subject::factory()->core()->create([
            'name' => 'Math',
            'code' => 'MTH',
            'weekly_periods' => 5,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 4,
            'max_periods_per_week' => 5,
            'weekly_periods' => 5,
            'is_active' => true,
        ]);

        // 7 periods when max is 5
        $slots = [
            1 => [
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
            ],
            2 => [
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
            ],
            3 => [
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'Math'],
            ],
            4 => [
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Math'], // 7th - exceeds max
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'weekly_maximum');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Max allowed: 5', $error['message']);
    }

    public function test_warns_about_cognitive_load(): void
    {
        $math = Subject::factory()->core()->create(['name' => 'Math']);
        $science = Subject::factory()->core()->create(['name' => 'Science']);

        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math'],
                2 => ['subject_id' => $science->id, 'subject_name' => 'Science'], // Consecutive heavy subjects
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_warnings']);

        $warning = collect($result['warnings'])->firstWhere('type', 'cognitive_load');
        $this->assertNotNull($warning);
        $this->assertStringContainsString('Consecutive demanding subjects', $warning['message']);
        $this->assertEquals(1, $warning['period']);
    }

    public function test_detects_teacher_conflicts_across_classes(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Mr. Smith']);
        $subject = Subject::factory()->core()->create(['name' => 'Math']);
        $anotherClass = ClassRoom::factory()->create(['name' => 'Class 6', 'section' => 'B']);

        // Create a conflicting slot in another class
        TimetableSlot::factory()->create([
            'class_room_id' => $anotherClass->id,
            'academic_term_id' => $this->term->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'day' => 1,
            'period' => 1,
        ]);

        // Try to assign same teacher at same time
        $slots = [
            1 => [
                1 => [
                    'subject_id' => $subject->id,
                    'subject_name' => 'Math',
                    'teacher_id' => $teacher->id,
                    'teacher_name' => 'Mr. Smith',
                ],
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'teacher_conflict');
        $this->assertNotNull($error);
        $this->assertStringContainsString('already assigned', $error['message']);
    }

    public function test_detects_teacher_workload_violation(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Ms. Johnson']);
        $subject = Subject::factory()->core()->create(['name' => 'English']);

        // Assign teacher to 7 periods in one day (max is 6)
        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'],
                3 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'],
                4 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'],
                5 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'],
                6 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'],
                7 => ['subject_id' => $subject->id, 'subject_name' => 'English', 'teacher_id' => $teacher->id, 'teacher_name' => 'Ms. Johnson'], // 7th - violation
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertTrue($result['has_errors']);

        $error = collect($result['errors'])->firstWhere('type', 'teacher_workload');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Max: 6', $error['message']);
        $this->assertStringContainsString('7 periods', $error['message']);
    }

    public function test_validates_fully_allocated_timetable_without_errors(): void
    {
        $math = Subject::factory()->core()->create(['name' => 'Math', 'weekly_periods' => 6]);
        $english = Subject::factory()->core()->create(['name' => 'English', 'weekly_periods' => 6]);
        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $math->id,
            'min_periods_per_week' => 5,
            'max_periods_per_week' => 6,
            'weekly_periods' => 6,
            'is_active' => true,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $english->id,
            'min_periods_per_week' => 5,
            'max_periods_per_week' => 6,
            'weekly_periods' => 6,
            'is_active' => true,
        ]);

        // Valid allocation
        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math', 'teacher_id' => $teacher1->id, 'teacher_name' => $teacher1->name],
                2 => ['subject_id' => $english->id, 'subject_name' => 'English', 'teacher_id' => $teacher2->id, 'teacher_name' => $teacher2->name],
            ],
            2 => [ // Tuesday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math', 'teacher_id' => $teacher1->id, 'teacher_name' => $teacher1->name],
                2 => ['subject_id' => $english->id, 'subject_name' => 'English', 'teacher_id' => $teacher2->id, 'teacher_name' => $teacher2->name],
            ],
            3 => [ // Wednesday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math', 'teacher_id' => $teacher1->id, 'teacher_name' => $teacher1->name],
                2 => ['subject_id' => $english->id, 'subject_name' => 'English', 'teacher_id' => $teacher2->id, 'teacher_name' => $teacher2->name],
            ],
            4 => [ // Thursday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math', 'teacher_id' => $teacher1->id, 'teacher_name' => $teacher1->name],
                2 => ['subject_id' => $english->id, 'subject_name' => 'English', 'teacher_id' => $teacher2->id, 'teacher_name' => $teacher2->name],
            ],
            5 => [ // Friday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math', 'teacher_id' => $teacher1->id, 'teacher_name' => $teacher1->name],
                2 => ['subject_id' => $english->id, 'subject_name' => 'English', 'teacher_id' => $teacher2->id, 'teacher_name' => $teacher2->name],
            ],
            6 => [ // Saturday
                1 => ['subject_id' => $math->id, 'subject_name' => 'Math', 'teacher_id' => $teacher1->id, 'teacher_name' => $teacher1->name],
                2 => ['subject_id' => $english->id, 'subject_name' => 'English', 'teacher_id' => $teacher2->id, 'teacher_name' => $teacher2->name],
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertFalse($result['has_errors']);
    }

    public function test_validation_returns_correct_structure(): void
    {
        $slots = [];
        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('has_errors', $result);
        $this->assertArrayHasKey('has_warnings', $result);
        $this->assertIsBool($result['has_errors']);
        $this->assertIsBool($result['has_warnings']);
    }

    public function test_warns_about_positional_inconsistency(): void
    {
        $subject = Subject::factory()->core()->create(['name' => 'Social']);

        // Place same subject at very different periods across days
        $slots = [
            1 => [1 => ['subject_id' => $subject->id, 'subject_name' => 'Social']], // Period 1
            2 => [5 => ['subject_id' => $subject->id, 'subject_name' => 'Social']], // Period 5
            3 => [8 => ['subject_id' => $subject->id, 'subject_name' => 'Social']], // Period 8
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $warning = collect($result['warnings'])->firstWhere('type', 'positional_inconsistency');
        $this->assertNotNull($warning);
        $this->assertStringContainsString('varying positions', $warning['message']);
    }

    public function test_warns_about_daily_subject_repetition(): void
    {
        $subject = Subject::factory()->core()->create(['name' => 'Computer', 'weekly_periods' => 4]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 3,
            'max_periods_per_week' => 4,
            'weekly_periods' => 4,
            'is_active' => true,
        ]);

        // Same subject twice in one day
        $slots = [
            1 => [ // Monday
                1 => ['subject_id' => $subject->id, 'subject_name' => 'Computer'],
                2 => ['subject_id' => $subject->id, 'subject_name' => 'Computer'],
            ],
        ];

        $result = $this->validator->validate($slots, $this->classRoom->id, $this->term->id);

        $warning = collect($result['warnings'])->firstWhere('type', 'daily_repetition');
        $this->assertNotNull($warning);
        $this->assertStringContainsString('spreading across different days', $warning['message']);
    }

    public function test_validate_slot_assignment_detects_subject_daily_limit(): void
    {
        $subject = Subject::factory()->core()->create(['name' => 'Maths']);

        // Create 2 existing slots for the subject on Monday
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'day' => 1,
            'period' => 2,
        ]);

        // Try to add a third period for same subject on same day
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $subject->id,
            null,
            1, // Monday
            3  // Period 3
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'subject_daily_limit');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Maximum 2 periods per day', $error['message']);
    }

    public function test_validate_slot_assignment_allows_valid_subject(): void
    {
        $subject = Subject::factory()->core()->create(['name' => 'English']);

        // Create 1 existing slot for the subject on Monday
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'day' => 1,
            'period' => 1,
        ]);

        // Try to add a second period (should be allowed)
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $subject->id,
            null,
            1, // Monday
            3  // Period 3
        );

        // Should have warning about daily repetition but no errors
        $errors = collect($result['errors'])->where('type', 'subject_daily_limit')->all();
        $this->assertEmpty($errors);
    }

    public function test_validate_slot_assignment_detects_cocurricular_conflicts(): void
    {
        $cocurricular1 = Subject::factory()->create([
            'name' => 'Music',
            'type' => 'co_curricular',
            'status' => 'active',
        ]);

        $cocurricular2 = Subject::factory()->create([
            'name' => 'Sports',
            'type' => 'co_curricular',
            'status' => 'active',
        ]);

        // Create existing co-curricular slot on Monday
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $cocurricular1->id,
            'day' => 1,
            'period' => 5,
        ]);

        // Try to add a different co-curricular on same day
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $cocurricular2->id,
            null,
            1, // Monday
            6  // Period 6
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'multiple_cocurricular');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Only 1 co-curricular per day', $error['message']);
    }

    public function test_validate_slot_assignment_requires_consecutive_cocurricular_periods(): void
    {
        $cocurricular = Subject::factory()->create([
            'name' => 'Art',
            'type' => 'co_curricular',
            'status' => 'active',
        ]);

        // Create existing co-curricular slot on Monday period 5
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $cocurricular->id,
            'day' => 1,
            'period' => 5,
        ]);

        // Try to add non-consecutive period (period 7, should be period 6)
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $cocurricular->id,
            null,
            1, // Monday
            7  // Period 7 (not consecutive to period 5)
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'cocurricular_not_consecutive');
        $this->assertNotNull($error);
        $this->assertStringContainsString('must be consecutive', $error['message']);
    }

    public function test_validate_slot_assignment_detects_teacher_conflicts(): void
    {
        $teacher = Teacher::factory()->create();
        $subject = Subject::factory()->core()->create();
        $otherClass = ClassRoom::factory()->create();

        // Create existing slot for teacher in another class
        TimetableSlot::factory()->create([
            'class_room_id' => $otherClass->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day' => 1,
            'period' => 3,
        ]);

        // Try to assign same teacher at same time to this class
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $subject->id,
            $teacher->id,
            1, // Monday
            3  // Period 3
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'teacher_conflict');
        $this->assertNotNull($error);
        $this->assertStringContainsString('already assigned', $error['message']);
    }

    public function test_validate_slot_assignment_detects_teacher_workload_limit(): void
    {
        $teacher = Teacher::factory()->create();
        $subject = Subject::factory()->core()->create();

        // Create 6 existing slots for teacher on Monday (max allowed)
        for ($period = 1; $period <= 6; $period++) {
            TimetableSlot::factory()->create([
                'class_room_id' => $this->classRoom->id,
                'academic_term_id' => $this->term->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'day' => 1,
                'period' => $period,
            ]);
        }

        // Try to add 7th period
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $subject->id,
            $teacher->id,
            1, // Monday
            7  // Period 7
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'teacher_workload');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Maximum 6 periods per day', $error['message']);
    }

    public function test_validate_slot_assignment_checks_weekly_maximum(): void
    {
        $subject = Subject::factory()->core()->create();

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 3,
            'max_periods_per_week' => 4,
            'weekly_periods' => 4,
            'is_active' => true,
        ]);

        // Create 4 existing slots (at max)
        for ($day = 1; $day <= 2; $day++) {
            for ($period = 1; $period <= 2; $period++) {
                TimetableSlot::factory()->create([
                    'class_room_id' => $this->classRoom->id,
                    'academic_term_id' => $this->term->id,
                    'subject_id' => $subject->id,
                    'day' => $day,
                    'period' => $period,
                ]);
            }
        }

        // Try to add 5th period
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $subject->id,
            null,
            3, // Wednesday
            1  // Period 1
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'weekly_maximum');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Max allowed: 4', $error['message']);
    }

    public function test_validate_complete_timetable_checks_all_slots(): void
    {
        $subject1 = Subject::factory()->core()->create(['name' => 'English']);
        $subject2 = Subject::factory()->core()->create(['name' => 'Maths']);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject1->id,
            'min_periods_per_week' => 5,
            'max_periods_per_week' => 6,
            'weekly_periods' => 5,
            'is_active' => true,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject2->id,
            'min_periods_per_week' => 6,
            'max_periods_per_week' => 8,
            'weekly_periods' => 6,
            'is_active' => true,
        ]);

        // Create some slots (not enough to meet minimums)
        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject1->id,
            'day' => 1,
            'period' => 1,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject2->id,
            'day' => 1,
            'period' => 2,
        ]);

        $result = $this->validator->validateCompleteTimetable(
            $this->classRoom->id,
            $this->term->id
        );

        // Should have errors about weekly minimums
        $this->assertTrue($result['has_errors']);
        $weeklyErrors = collect($result['errors'])->where('type', 'weekly_minimum')->all();
        $this->assertNotEmpty($weeklyErrors);

        // Should have warnings about empty slots
        $this->assertTrue($result['has_warnings']);
        $emptySlotWarning = collect($result['warnings'])->firstWhere('type', 'empty_slots');
        $this->assertNotNull($emptySlotWarning);
    }

    public function test_validate_complete_timetable_accepts_valid_timetable(): void
    {
        $subject = Subject::factory()->core()->create();

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'min_periods_per_week' => 4,
            'max_periods_per_week' => 6,
            'weekly_periods' => 5,
            'is_active' => true,
        ]);

        // Create exactly 5 slots (within limits, not on same day more than twice)
        $days = [1, 1, 2, 3, 4];
        $periods = [1, 3, 2, 1, 1];

        foreach ($days as $index => $day) {
            TimetableSlot::factory()->create([
                'class_room_id' => $this->classRoom->id,
                'academic_term_id' => $this->term->id,
                'subject_id' => $subject->id,
                'day' => $day,
                'period' => $periods[$index],
            ]);
        }

        $result = $this->validator->validateCompleteTimetable(
            $this->classRoom->id,
            $this->term->id
        );

        // Should have warnings about empty slots but no hard errors about this subject
        $subjectErrors = collect($result['errors'])
            ->filter(fn ($error) => isset($error['subject_id']) && $error['subject_id'] === $subject->id)
            ->all();

        $this->assertEmpty($subjectErrors);
    }

    public function test_validate_slot_assignment_returns_proper_structure(): void
    {
        $subject = Subject::factory()->core()->create();

        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            $subject->id,
            null,
            1,
            1
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertIsArray($result['errors']);
        $this->assertIsArray($result['warnings']);
    }

    public function test_validate_slot_assignment_handles_invalid_subject(): void
    {
        $result = $this->validator->validateSlotAssignment(
            $this->classRoom->id,
            $this->term->id,
            9999, // Non-existent subject
            null,
            1,
            1
        );

        $this->assertNotEmpty($result['errors']);
        $error = collect($result['errors'])->firstWhere('type', 'invalid_subject');
        $this->assertNotNull($error);
    }
}
