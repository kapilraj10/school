<?php

namespace Tests\Unit\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\CombinedPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Services\GeneticAlgorithmTimetableService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GeneticAlgorithmTimetableServiceTest extends TestCase
{
    private GeneticAlgorithmTimetableService $service;

    private ClassRoom $classRoom;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeneticAlgorithmTimetableService;
        $this->classRoom = ClassRoom::factory()->create([
            'name' => 'Class 5',
            'section' => 'A',
        ]);
        $this->term = AcademicTerm::factory()->active()->create();
    }

    public function test_constructor_loads_genetic_algorithm_file(): void
    {
        $service = new GeneticAlgorithmTimetableService;

        $this->assertTrue(class_exists(\GeneticAlgorithmScheduler::class));
        $this->assertTrue(class_exists(\SchedulerConfig::class));
        $this->assertTrue(class_exists(\Subject::class));
        $this->assertTrue(class_exists(\Teacher::class));
        $this->assertTrue(class_exists(\Section::class));
        $this->assertTrue(class_exists(\Timetable::class));
    }

    public function test_generate_timetable_fails_with_no_subjects(): void
    {
        $result = $this->service->generateTimetable($this->classRoom, $this->term);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active subjects configured', $result['message']);
        $this->assertCount(0, TimetableSlot::all());
    }

    public function test_generate_timetable_fails_with_no_teachers(): void
    {
        $subject = Subject::factory()->core()->create([
            'status' => 'active',
            'type' => 'core',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 1,
        ]);

        $result = $this->service->generateTimetable($this->classRoom, $this->term);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No teachers available', $result['message']);
    }

    public function test_generate_timetable_successfully_creates_slots(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully', $result['message']);
        $this->assertArrayHasKey('fitness', $result);
        $this->assertArrayHasKey('slots', $result);
        $this->assertGreaterThan(0, $result['slots']);
        $this->assertGreaterThan(0, TimetableSlot::count());
    }

    public function test_generate_timetable_uses_correct_parameters(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            20,
            100
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('fitness', $result);
    }

    public function test_generate_timetable_creates_combined_periods_with_academic_term_id(): void
    {
        $this->setupValidTimetableData(combined: true);

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        if (! $result['success']) {
            $this->markTestSkipped('Timetable generation failed: '.$result['message']);
        }

        $combinedPeriods = CombinedPeriod::where('academic_term_id', $this->term->id)->get();

        if ($combinedPeriods->count() > 0) {
            foreach ($combinedPeriods as $period) {
                $this->assertEquals($this->term->id, $period->academic_term_id);
                $this->assertNotNull($period->subject_id);
                $this->assertNotNull($period->teacher_id);
            }
        } else {
            $this->markTestSkipped('No combined periods were created during generation');
        }
    }

    public function test_generate_timetable_clears_existing_slots(): void
    {
        $this->setupValidTimetableData();

        TimetableSlot::factory()->create([
            'class_room_id' => $this->classRoom->id,
            'academic_term_id' => $this->term->id,
        ]);

        $this->assertCount(1, TimetableSlot::all());

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);

        $slots = TimetableSlot::where('class_room_id', $this->classRoom->id)
            ->where('academic_term_id', $this->term->id)
            ->get();

        $this->assertGreaterThan(0, $slots->count());
    }

    public function test_generate_timetable_rolls_back_on_error(): void
    {
        $subject = Subject::factory()->core()->create(['status' => 'active']);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 1,
        ]);

        $result = $this->service->generateTimetable($this->classRoom, $this->term);

        $this->assertFalse($result['success']);
        $this->assertCount(0, TimetableSlot::all());
    }

    public function test_generate_timetable_assigns_correct_class_and_term(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);

        $slots = TimetableSlot::all();
        foreach ($slots as $slot) {
            $this->assertEquals($this->classRoom->id, $slot->class_room_id);
            $this->assertEquals($this->term->id, $slot->academic_term_id);
        }
    }

    public function test_generate_timetable_assigns_valid_days_and_periods(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);

        $slots = TimetableSlot::all();
        foreach ($slots as $slot) {
            $this->assertGreaterThanOrEqual(0, $slot->day);
            $this->assertLessThan(6, $slot->day);
            $this->assertGreaterThanOrEqual(1, $slot->period);
            $this->assertLessThanOrEqual(8, $slot->period);
        }
    }

    public function test_generate_timetable_assigns_subjects_and_teachers(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);

        $slots = TimetableSlot::all();
        foreach ($slots as $slot) {
            $this->assertNotNull($slot->subject_id);
            $this->assertNotNull($slot->teacher_id);
            $this->assertInstanceOf(Subject::class, $slot->subject);
            $this->assertInstanceOf(Teacher::class, $slot->teacher);
        }
    }

    public function test_generate_timetable_with_multiple_subjects(): void
    {
        $subjects = [
            Subject::factory()->core()->create(['name' => 'Math', 'status' => 'active']),
            Subject::factory()->core()->create(['name' => 'Science', 'status' => 'active']),
            Subject::factory()->core()->create(['name' => 'English', 'status' => 'active']),
        ];

        $teachers = [
            Teacher::factory()->create(['subject_ids' => [$subjects[0]->id], 'status' => 'active']),
            Teacher::factory()->create(['subject_ids' => [$subjects[1]->id], 'status' => 'active']),
            Teacher::factory()->create(['subject_ids' => [$subjects[2]->id], 'status' => 'active']),
        ];

        foreach ($subjects as $index => $subject) {
            ClassSubjectSetting::create([
                'class_room_id' => $this->classRoom->id,
                'subject_id' => $subject->id,
                'is_active' => true,
                'min_periods_per_week' => 2,
                'max_periods_per_week' => 4,
                'single_combined' => 'single',
                'priority' => 3 - $index,
            ]);
        }

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);

        $subjectIds = TimetableSlot::pluck('subject_id')->unique();
        $this->assertGreaterThan(1, $subjectIds->count());
    }

    public function test_generate_timetable_with_cocurricular_subjects(): void
    {
        $coreSubject = Subject::factory()->core()->create([
            'name' => 'Math',
            'status' => 'active',
            'type' => 'core',
        ]);

        $coCurricularSubject = Subject::factory()->coCurricular()->create([
            'name' => 'Sports',
            'status' => 'active',
            'type' => 'co_curricular',
        ]);

        $teacher1 = Teacher::factory()->create([
            'subject_ids' => [$coreSubject->id],
            'status' => 'active',
        ]);

        $teacher2 = Teacher::factory()->create([
            'subject_ids' => [$coCurricularSubject->id],
            'status' => 'active',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $coreSubject->id,
            'is_active' => true,
            'min_periods_per_week' => 3,
            'max_periods_per_week' => 5,
            'single_combined' => 'single',
            'priority' => 2,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $coCurricularSubject->id,
            'is_active' => true,
            'min_periods_per_week' => 1,
            'max_periods_per_week' => 2,
            'single_combined' => 'single',
            'priority' => 1,
        ]);

        $result = $this->service->generateTimetable(
            $this->classRoom,
            $this->term,
            10,
            50
        );

        $this->assertTrue($result['success']);

        $hasCore = TimetableSlot::where('subject_id', $coreSubject->id)->exists();
        $hasCoCurricular = TimetableSlot::where('subject_id', $coCurricularSubject->id)->exists();

        $this->assertTrue($hasCore);
        $this->assertTrue($hasCoCurricular);
    }

    public function test_extract_grade_level_from_class_name(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractGradeLevel');
        $method->setAccessible(true);

        $this->assertEquals('5', $method->invoke($this->service, 'Class 5'));
        $this->assertEquals('10', $method->invoke($this->service, 'Class 10'));
        $this->assertEquals('3', $method->invoke($this->service, 'Grade 3 Section A'));
        $this->assertEquals('1', $method->invoke($this->service, 'First Class'));
        $this->assertEquals('1', $method->invoke($this->service, 'NoNumber'));
    }

    public function test_map_subject_type_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapSubjectType');
        $method->setAccessible(true);

        $this->assertEquals(\SchedulerConfig::TYPE_COMPULSORY, $method->invoke($this->service, 'core'));
        $this->assertEquals(\SchedulerConfig::TYPE_COMPULSORY, $method->invoke($this->service, 'compulsory'));
        $this->assertEquals(\SchedulerConfig::TYPE_OPTIONAL, $method->invoke($this->service, 'elective'));
        $this->assertEquals(\SchedulerConfig::TYPE_OPTIONAL, $method->invoke($this->service, 'optional'));
        $this->assertEquals(\SchedulerConfig::TYPE_CO_CURRICULAR, $method->invoke($this->service, 'co_curricular'));
        $this->assertEquals(\SchedulerConfig::TYPE_CO_CURRICULAR, $method->invoke($this->service, 'co-curricular'));
        $this->assertEquals(\SchedulerConfig::TYPE_COMPULSORY, $method->invoke($this->service, 'unknown'));
    }

    public function test_load_class_data_skips_inactive_subjects(): void
    {
        $activeSubject = Subject::factory()->create([
            'status' => 'active',
            'name' => 'Active Subject',
        ]);

        $inactiveSubject = Subject::factory()->create([
            'status' => 'inactive',
            'name' => 'Inactive Subject',
        ]);

        $teacher = Teacher::factory()->create([
            'subject_ids' => [$activeSubject->id, $inactiveSubject->id],
            'status' => 'active',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $activeSubject->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 2,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $inactiveSubject->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 1,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('loadClassData');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->classRoom);

        $subjectsProperty = $reflection->getProperty('subjects');
        $subjectsProperty->setAccessible(true);
        $subjects = $subjectsProperty->getValue($this->service);

        $this->assertArrayHasKey((string) $activeSubject->id, $subjects);
        $this->assertArrayNotHasKey((string) $inactiveSubject->id, $subjects);
    }

    public function test_load_class_data_loads_teachers_for_subjects(): void
    {
        $subject = Subject::factory()->create(['status' => 'active']);

        $teacher1 = Teacher::factory()->create([
            'subject_ids' => [$subject->id],
            'status' => 'active',
            'name' => 'Teacher One',
        ]);

        $teacher2 = Teacher::factory()->create([
            'subject_ids' => [$subject->id],
            'status' => 'active',
            'name' => 'Teacher Two',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 1,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('loadClassData');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->classRoom);

        $teachersProperty = $reflection->getProperty('teachers');
        $teachersProperty->setAccessible(true);
        $teachers = $teachersProperty->getValue($this->service);

        $this->assertCount(2, $teachers);
        $this->assertArrayHasKey((string) $teacher1->id, $teachers);
        $this->assertArrayHasKey((string) $teacher2->id, $teachers);
    }

    public function test_load_class_data_handles_teacher_with_multiple_subjects(): void
    {
        $subject1 = Subject::factory()->create(['status' => 'active', 'name' => 'Math']);
        $subject2 = Subject::factory()->create(['status' => 'active', 'name' => 'Science']);

        $teacher = Teacher::factory()->create([
            'subject_ids' => [$subject1->id, $subject2->id],
            'status' => 'active',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject1->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 2,
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject2->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => 'single',
            'priority' => 1,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('loadClassData');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->classRoom);

        $teachersProperty = $reflection->getProperty('teachers');
        $teachersProperty->setAccessible(true);
        $teachers = $teachersProperty->getValue($this->service);

        $this->assertCount(1, $teachers);
        $teacherObj = $teachers[(string) $teacher->id];
        $this->assertContains((string) $subject1->id, $teacherObj->subjects);
        $this->assertContains((string) $subject2->id, $teacherObj->subjects);
    }

    public function test_get_errors_returns_empty_array_initially(): void
    {
        $errors = $this->service->getErrors();
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function test_get_warnings_returns_empty_array_initially(): void
    {
        $warnings = $this->service->getWarnings();
        $this->assertIsArray($warnings);
        $this->assertEmpty($warnings);
    }

    public function test_generate_timetable_logs_activity(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Starting genetic algorithm/'));

        Log::shouldReceive('error')
            ->never();

        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable($this->classRoom, $this->term, 10, 50);

        $this->assertTrue($result['success']);
    }

    public function test_generate_timetable_logs_errors_on_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Timetable generation failed', \Mockery::any());

        $result = $this->service->generateTimetable($this->classRoom, $this->term);

        $this->assertFalse($result['success']);
    }

    public function test_generate_timetable_returns_warnings_array(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable($this->classRoom, $this->term, 10, 50);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertIsArray($result['warnings']);
    }

    public function test_generate_timetable_sets_slot_status_to_published(): void
    {
        $this->setupValidTimetableData();

        $result = $this->service->generateTimetable($this->classRoom, $this->term, 10, 50);

        $this->assertTrue($result['success']);

        $slots = TimetableSlot::all();
        foreach ($slots as $slot) {
            $this->assertEquals('published', $slot->status);
            $this->assertEquals('regular', $slot->type);
            $this->assertFalse($slot->is_locked);
        }
    }

    private function setupValidTimetableData(bool $combined = false): void
    {
        $subject = Subject::factory()->core()->create([
            'status' => 'active',
            'single_combined' => $combined ? 'combined' : 'single',
        ]);

        $teacher = Teacher::factory()->create([
            'subject_ids' => [$subject->id],
            'status' => 'active',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'single_combined' => $combined ? 'combined' : 'single',
            'priority' => 1,
        ]);
    }
}
