<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlogContactPageTest extends TestCase
{
    public function test_blog_page_returns_successful_response(): void
    {
        $response = $this->get('/blog');

        $response
            ->assertOk()
            ->assertSee('School Blog');
    }

    public function test_contact_page_returns_successful_response(): void
    {
        $response = $this->get('/contact');

        $response
            ->assertOk()
            ->assertSee('Contact Us')
            ->assertSee('info@ybms.com');
    }
}
