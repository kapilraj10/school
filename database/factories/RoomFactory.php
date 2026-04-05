<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Room '.fake()->unique()->numberBetween(101, 999),
            'code' => 'RM-'.fake()->unique()->numberBetween(101, 999),
            'type' => fake()->randomElement(['classroom', 'computer_lab', 'science_lab']),
            'capacity' => fake()->numberBetween(20, 60),
            'status' => 'active',
            'notes' => null,
        ];
    }
}
