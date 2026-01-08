<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimetableSlot>
 */
class TimetableSlotFactory extends Factory
{
    protected $model = TimetableSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_room_id' => ClassRoom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'combined_period_id' => null,
            'day' => fake()->numberBetween(1, 6),
            'period' => fake()->numberBetween(1, 8),
            'start_time' => now()->setTime(8, 0),
            'end_time' => now()->setTime(8, 45),
            'type' => 'regular',
            'is_locked' => false,
            'is_combined' => false,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the slot is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
        ]);
    }

    /**
     * Indicate that the slot is combined.
     */
    public function combined(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_combined' => true,
            'type' => 'combined',
        ]);
    }

    /**
     * Set specific day and period.
     */
    public function at(int $day, int $period): static
    {
        return $this->state(fn (array $attributes) => [
            'day' => $day,
            'period' => $period,
        ]);
    }

    /**
     * Set for specific class and term.
     */
    public function forClassAndTerm(int $classRoomId, int $termId): static
    {
        return $this->state(fn (array $attributes) => [
            'class_room_id' => $classRoomId,
            'academic_term_id' => $termId,
        ]);
    }
}
