<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Tests\TestCase;

class BlogPostDetailPageTest extends TestCase
{
    public function test_published_blog_post_detail_page_returns_successful_response(): void
    {
        $post = BlogPost::factory()->create([
            'title' => 'My School Event',
            'slug' => 'my-school-event',
            'status' => 'published',
            'published_at' => now(),
            'content' => 'Event details content',
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response
            ->assertOk()
            ->assertSee('MY SCHOOL EVENT')
            ->assertSee('BACK TO BLOG');
    }
}
