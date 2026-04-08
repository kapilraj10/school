<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('blog_posts')) {
            return view('blog', [
                'blogPosts' => collect(),
                'blogTags' => [],
            ]);
        }

        $tag = request('tag');
        $search = request('search');

        $postsQuery = BlogPost::query()
            ->with('author:id,name')
            ->published()
            ->when(filled($tag), function ($query) use ($tag): void {
                $query->whereJsonContains('tags', $tag);
            })
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->latest('published_at')
            ->latest('id');

        $posts = $postsQuery
            ->take(12)
            ->get();

        $tags = $posts
            ->flatMap(fn (BlogPost $post) => $post->tags ?? [])
            ->filter(fn ($tag) => is_string($tag) && $tag !== '')
            ->unique()
            ->values()
            ->all();

        return view('blog', [
            'blogPosts' => $posts,
            'blogTags' => $tags,
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->with('author:id,name')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $recentPosts = BlogPost::query()
            ->published()
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->latest('id')
            ->take(5)
            ->get();

        return view('blog-show', [
            'post' => $post,
            'recentPosts' => $recentPosts,
        ]);
    }
}
