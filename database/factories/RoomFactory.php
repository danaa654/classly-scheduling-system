<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_name' => 'Room ' . $this->faker->numberBetween(101, 999),
            'capacity' => $this->faker->numberBetween(30, 100),
            'building' => $this->faker->randomElement(['Building A', 'Building B', 'Building C']),
            'is_available' => true,
        ];
    }
}