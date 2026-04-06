<?php

namespace Tests\Feature;

use Tests\TestCase;

class GalleryPageTest extends TestCase
{
    public function test_gallery_page_returns_successful_response(): void
    {
        $response = $this->get('/gallery');

        $response
            ->assertOk()
            ->assertSee('Gallery')
            ->assertSee('YUMAK BAUDDHA MANDAL SCHOOL');
    }
}
