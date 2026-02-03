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
            'availability_matrix' => [
                'Sun' => [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true, 7 => true, 8 => true],
                'Mon' => [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true, 7 => true, 8 => true],
                'Tue' => [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true, 7 => true, 8 => true],
                'Wed' => [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true, 7 => true, 8 => true],
                'Thu' => [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true, 7 => true, 8 => true],
                'Fri' => [1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true, 7 => true, 8 => true],
            ],
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
     * Set available days with all periods enabled.
     */
    public function availableDays(array $days): static
    {
        return $this->state(function (array $attributes) use ($days) {
            $matrix = [];
            foreach ($days as $day) {
                $matrix[$day] = [
                    1 => true, 2 => true, 3 => true, 4 => true,
                    5 => true, 6 => true, 7 => true, 8 => true,
                ];
            }

            return ['availability_matrix' => $matrix];
        });
    }
}
