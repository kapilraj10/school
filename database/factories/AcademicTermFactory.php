<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    protected $model = AcademicTerm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->year();
        $term = fake()->numberBetween(1, 3);

        return [
            'name' => "{$year} - Term {$term}",
            'year' => $year,
            'term' => $term,
            'start_date' => now()->startOfYear()->addMonths(($term - 1) * 4),
            'end_date' => now()->startOfYear()->addMonths($term * 4)->subDay(),
            'is_active' => false,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the term is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the term is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
