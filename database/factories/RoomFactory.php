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
            'type' => $this->faker->randomElement(['Lecture', 'Lab', 'Workshop']),
            'specialization' => $this->faker->randomElement(['IT', 'CCS', 'ACT', 'GENERAL']),
            'floor' => $this->faker->randomElement(['Ground', '1st', '2nd', '3rd']),
        ];
    }
}