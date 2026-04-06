<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    public function test_about_page_returns_successful_response(): void
    {
        $response = $this->get('/about');

        $response
            ->assertOk()
            ->assertSee('YUMAK BAUDDHA MANDAL SCHOOL')
            ->assertSee('About Us');
    }
}
