<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffPageTest extends TestCase
{
    public function test_staff_page_returns_successful_response(): void
    {
        $response = $this->get('/staff');

        $response
            ->assertOk()
            ->assertSee('Our Team')
            ->assertSee('Our Teachers')
            ->assertSeeInOrder(['Principal', 'Vice Principal', 'Coordinator'])
            ->assertSee('YUMAK BAUDDHA MANDAL SCHOOL');
    }
}
