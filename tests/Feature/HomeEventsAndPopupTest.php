<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\SpecialEvent;
use Tests\TestCase;

class HomeEventsAndPopupTest extends TestCase
{
    public function test_home_page_renders_admin_managed_events_and_notice_link(): void
    {
        $term = AcademicTerm::factory()->active()->create();

        SpecialEvent::query()->create([
            'academic_term_id' => $term->id,
            'class_room_id' => null,
            'name' => 'Board Exam Orientation',
            'event_type' => 'program',
            'date' => now()->addDays(5)->toDateString(),
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'is_school_wide' => true,
            'blocks_timetable' => false,
            'description' => 'Important meeting for all students and parents.',
            'venue' => 'Main Hall',
            'notice_url' => 'https://example.com/notice.pdf',
            'notice_link_text' => 'View Notice',
            'show_on_home' => true,
            'show_popup' => false,
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Board Exam Orientation')
            ->assertSee('Main Hall')
            ->assertSee('View Notice')
            ->assertSee('https://example.com/notice.pdf', false);
    }

    public function test_home_page_renders_popup_markup_when_popup_event_is_enabled(): void
    {
        $term = AcademicTerm::factory()->active()->create();

        SpecialEvent::query()->create([
            'academic_term_id' => $term->id,
            'class_room_id' => null,
            'name' => 'Admission Notice',
            'event_type' => 'event',
            'date' => now()->addDays(2)->toDateString(),
            'day_of_week' => 1,
            'is_school_wide' => true,
            'blocks_timetable' => false,
            'description' => 'Admissions are open now.',
            'show_on_home' => true,
            'show_popup' => true,
            'popup_image' => 'https://example.com/popup.jpg',
            'notice_url' => 'https://example.com/admission',
            'notice_link_text' => 'View Notice',
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('id="eventPopupOverlay"', false)
            ->assertSee('Admissions are open now.')
            ->assertSee('https://example.com/popup.jpg', false)
            ->assertSee('View Notice');
    }
}
