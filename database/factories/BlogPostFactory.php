<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(4, true),
            'tags' => fake()->randomElements(['School', 'Education', 'Events', 'Activities', 'Students'], rand(1, 3)),
            'status' => fake()->randomElement(['draft', 'published']),
            'published_at' => now(),
        ];
    }
}
