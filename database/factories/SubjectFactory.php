<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'subject_code' => strtoupper($this->faker->unique()->bothify('???###')),
            'description' => $this->faker->sentence(3),
            'department' => $this->faker->randomElement(['IT', 'CCS', 'ACT']),
            'major' => $this->faker->randomElement(['Systems', 'Networks', 'Web']),
            'year_level' => $this->faker->numberBetween(1, 4),
            'section' => $this->faker->randomElement(['A', 'B', 'C']),
            'units' => $this->faker->numberBetween(1, 4),
            'duration_hours' => $this->faker->randomElement([1, 1.5, 2, 3]),
            'meetings_per_week' => $this->faker->numberBetween(1, 3),
            'type' => $this->faker->randomElement(['major', 'minor']),
            'semester' => 1,
            'school_year' => '2026-2027',
            'is_available' => true,
        ];
    }
}