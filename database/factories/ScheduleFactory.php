<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 16);
        $startMinute = $this->faker->randomElement([0, 30]);
        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
        $endTime = sprintf('%02d:30:00', $startHour + 1);

        return [
            'subject_id' => Subject::factory(),
            'room_id' => Room::factory(),
            'day' => $this->faker->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'section' => $this->faker->randomElement(['A', 'B', 'C']),
            'status' => 'draft',
            'semester' => 1,
            'school_year' => '2026-2027',
        ];
    }
}