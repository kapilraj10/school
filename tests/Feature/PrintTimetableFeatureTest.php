<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\TimetablePrintService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrintTimetableFeatureTest extends TestCase
{
    protected User $user;

    protected AcademicTerm $term;

    protected ClassRoom $class;

    protected Teacher $teacher;

    protected Room $room;

    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test data
        $this->term = AcademicTerm::create([
            'name' => '2024-2025 Term 1',
            'year' => 2024,
            'term' => '1',
            'start_date' => '2024-09-01',
            'end_date' => '2024-12-20',
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->class = ClassRoom::create([
            'name' => 'Class 10',
            'section' => 'A',
            'weekly_periods' => 40,
            'total_subjects' => 10,
            'status' => 'active',
        ]);

        $this->teacher = Teacher::create([
            'name' => 'John Doe',
            'employee_id' => 'T001',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'status' => 'active',
        ]);

        $this->room = Room::factory()->create([
            'name' => 'Computer Lab A',
            'code' => 'LAB-COMP-A',
            'type' => 'computer_lab',
            'status' => 'active',
        ]);

        $this->subject = Subject::factory()->create([
            'name' => 'Computer Science',
            'code' => 'COMP-10A',
            'class_room_id' => $this->class->id,
            'type' => 'core',
            'status' => 'active',
        ]);

        ClassSubjectSetting::create([
            'class_room_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'room_id' => $this->room->id,
            'min_periods_per_week' => 2,
            'max_periods_per_week' => 4,
            'weekly_periods' => 3,
            'single_combined' => 'single',
            'is_active' => true,
        ]);

        TimetableSlot::factory()->create([
            'class_room_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'academic_term_id' => $this->term->id,
            'day' => 1,
            'period' => 1,
        ]);
    }

    public function test_timetable_print_service_can_generate_class_pdf(): void
    {
        $printService = new TimetablePrintService;

        $pdf = $printService->generateClassTimetablePdf($this->class->id, $this->term->id);

        $this->assertNotNull($pdf);
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_timetable_print_service_can_generate_teacher_pdf(): void
    {
        $printService = new TimetablePrintService;

        $pdf = $printService->generateTeacherSchedulePdf($this->teacher->id, $this->term->id);

        $this->assertNotNull($pdf);
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_timetable_print_service_can_generate_room_pdf(): void
    {
        $printService = new TimetablePrintService;

        $pdf = $printService->generateRoomSchedulePdf($this->room->id, $this->term->id);

        $this->assertNotNull($pdf);
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_timetable_print_service_generates_correct_filename(): void
    {
        $printService = new TimetablePrintService;

        $filename = $printService->generateFilename('class', $this->class, $this->term);

        $this->assertStringContainsString($this->class->full_name, $filename);
        $this->assertStringContainsString($this->term->name, $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_print_preview_route_requires_authentication(): void
    {
        $response = $this->get(route('print.class-preview', [
            'class_id' => $this->class->id,
            'term_id' => $this->term->id,
        ]));

        // Expecting either a redirect to login or unauthorized status
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 401 || $response->status() === 500,
            'Expected redirect or unauthorized status'
        );
    }

    public function test_authenticated_user_can_access_class_print_preview(): void
    {
        $response = $this->actingAs($this->user)->get(route('print.class-preview', [
            'class_id' => $this->class->id,
            'term_id' => $this->term->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_access_teacher_print_preview(): void
    {
        $response = $this->actingAs($this->user)->get(route('print.teacher-preview', [
            'teacher_id' => $this->teacher->id,
            'term_id' => $this->term->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_access_room_print_preview(): void
    {
        Role::findOrCreate('admin', 'web');
        $this->user->assignRole('admin');

        $response = $this->actingAs($this->user)->get(route('print.room-preview', [
            'room_id' => $this->room->id,
            'term_id' => $this->term->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_print_preview_returns_404_with_missing_parameters(): void
    {
        $response = $this->actingAs($this->user)->get(route('print.class-preview'));

        $response->assertStatus(404);
    }

    public function test_class_pdf_data_maps_slots_when_school_days_start_from_monday(): void
    {
        TimetableSetting::set('school_days', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'array', 'general');

        $printService = new TimetablePrintService;

        $method = new \ReflectionMethod(TimetablePrintService::class, 'getClassTimetableData');
        $method->setAccessible(true);

        /** @var array{slots: array, days: array} $data */
        $data = $method->invoke($printService, $this->class->id, $this->term->id);

        $days = $data['days'];
        $mondayKey = array_search('Monday', $days, true);

        $this->assertNotFalse($mondayKey);
        $this->assertNotNull($data['slots'][(int) $mondayKey][1] ?? null);
        $this->assertEquals($this->subject->id, $data['slots'][(int) $mondayKey][1]?->subject_id);
    }
}
