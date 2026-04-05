<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassRoom>
 */
class ClassRoomFactory extends Factory
{
    protected $model = ClassRoom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Class '.fake()->unique()->numberBetween(100, 99999),
            'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'weekly_periods' => 48,
            'total_subjects' => 8,
            'capacity' => 40,
            'status' => 'active',
            'class_teacher_id' => null,
        ];
    }

    /**
     * Indicate that the class is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set specific class name.
     */
    public function className(string $name, string $section): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'section' => $section,
        ]);
    }
}
