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
            'type' => 'core',
            'weekly_periods' => fake()->numberBetween(4, 6),
            'min_periods_per_week' => 4,
            'max_periods_per_week' => 6,
            'level' => 'intermediate',
            'status' => 'active',
            'class_range' => '1 - 5',
            'single_combined' => 'single',
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
            'weekly_periods' => 2,
            'min_periods_per_week' => 1,
            'max_periods_per_week' => 2,
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

    /**
     * Set specific weekly periods.
     */
    public function weeklyPeriods(int $periods): static
    {
        return $this->state(fn (array $attributes) => [
            'weekly_periods' => $periods,
            'min_periods_per_week' => $periods,
            'max_periods_per_week' => $periods,
        ]);
    }
}
