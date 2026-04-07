<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Tests\TestCase;

class BlogVisibilityTest extends TestCase
{
    public function test_published_posts_are_visible_on_blog_page(): void
    {
        BlogPost::factory()->create([
            'title' => 'Visible Blog Post',
            'slug' => 'visible-blog-post',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get('/blog');

        $response
            ->assertOk()
            ->assertSee('Visible Blog Post');
    }

    public function test_draft_posts_are_not_visible_on_blog_page(): void
    {
        BlogPost::factory()->create([
            'title' => 'Hidden Draft Post',
            'slug' => 'hidden-draft-post',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->get('/blog');

        $response
            ->assertOk()
            ->assertDontSee('Hidden Draft Post');
    }
}
