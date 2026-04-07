<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\TextCarouselItem;
use Tests\TestCase;

class HomeCarouselContentTest extends TestCase
{
    public function test_home_page_renders_active_hero_and_text_carousel_content(): void
    {
        HeroSlide::factory()->create([
            'title' => 'Hero Slide One',
            'subtitle' => 'Hero Subtitle One',
            'description' => 'Hero description one.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HeroSlide::factory()->create([
            'title' => 'Inactive Hero Slide',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        TextCarouselItem::factory()->create([
            'quote' => 'Parents are very happy with the school.',
            'author_name' => 'Parent One',
            'author_role' => 'Guardian',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        TextCarouselItem::factory()->create([
            'quote' => 'This quote should not be visible.',
            'author_name' => 'Parent Two',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Hero Slide One')
            ->assertDontSee('Inactive Hero Slide')
            ->assertSee('Parents are very happy with the school.')
            ->assertDontSee('This quote should not be visible.');
    }

    public function test_home_page_renders_with_fallback_carousel_content_when_no_records_exist(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Quality School Education')
            ->assertSee('Welcome to')
            ->assertSee('TESTIMONIAL')
            ->assertSee('Parents');
    }
}
