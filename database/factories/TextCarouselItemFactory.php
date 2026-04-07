<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TextCarouselItem>
 */
class TextCarouselItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote' => $this->faker->sentence(18),
            'author_name' => $this->faker->name(),
            'author_role' => $this->faker->jobTitle(),
            'author_image' => 'images/slide-2.png',
            'rating' => $this->faker->numberBetween(3, 5),
            'sort_order' => $this->faker->numberBetween(1, 50),
            'is_active' => true,
        ];
    }
}
