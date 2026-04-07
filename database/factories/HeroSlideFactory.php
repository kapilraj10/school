<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subtitle' => $this->faker->sentence(3),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(12),
            'button_text' => 'Read More',
            'button_link' => '#',
            'background_image' => 'images/slide1.png',
            'sort_order' => $this->faker->numberBetween(1, 50),
            'is_active' => true,
        ];
    }
}
