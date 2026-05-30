<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'employee_id' => $this->faker->unique()->numerify('EMP-####'),
            'department' => $this->faker->randomElement(['IT', 'CCS', 'ACT']),
            'specialization' => $this->faker->randomElement(['Systems', 'Networks', 'Web']),
            'availability' => json_encode([
                'Monday' => ['start' => '08:00', 'end' => '17:00'],
                'Tuesday' => ['start' => '08:00', 'end' => '17:00'],
                'Wednesday' => ['start' => '08:00', 'end' => '17:00'],
                'Thursday' => ['start' => '08:00', 'end' => '17:00'],
                'Friday' => ['start' => '08:00', 'end' => '17:00'],
            ]),
            'is_available' => true,
        ];
    }
}