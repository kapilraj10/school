<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Math', 'Science', 'English', 'Social', 'Nepali', 'Computer']),
            'code' => strtoupper(fake()->lexify('???')),
            'class_room_id' => \App\Models\ClassRoom::factory(),
            'type' => 'core',
            'level' => 'intermediate',
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the subject is a core subject.
     */
    public function core(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'core',
            'name' => fake()->randomElement(['Math', 'Science', 'English', 'Social', 'Nepali']),
        ]);
    }

    /**
     * Indicate that the subject is a co-curricular subject.
     */
    public function coCurricular(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'co_curricular',
            'name' => fake()->randomElement(['Dance', 'Music', 'Art', 'Sports', 'Drama']),
        ]);
    }

    /**
     * Indicate that the subject is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
