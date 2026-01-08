<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'employee_id' => 'EMP'.fake()->unique()->numberBetween(1000, 9999),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'subject_ids' => [],
            'max_periods_per_day' => 6,
            'max_periods_per_week' => 30,
            'available_days' => [1, 2, 3, 4, 5, 6],
            'available_periods' => [1, 2, 3, 4, 5, 6, 7, 8],
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the teacher is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set specific subjects for the teacher.
     */
    public function forSubjects(array $subjectIds): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_ids' => $subjectIds,
        ]);
    }

    /**
     * Set specific max periods per day.
     */
    public function maxPeriodsPerDay(int $periods): static
    {
        return $this->state(fn (array $attributes) => [
            'max_periods_per_day' => $periods,
        ]);
    }

    /**
     * Set available days.
     */
    public function availableDays(array $days): static
    {
        return $this->state(fn (array $attributes) => [
            'available_days' => $days,
        ]);
    }
}
