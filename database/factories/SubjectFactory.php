<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        // Generate EDP code in correct format: [MAJOR]-[YY][SEM][LEVEL][SFO]
        // Example: IT-2611001
        $major = $this->faker->randomElement(['IT', 'CCS', 'ACT']);
        $year = $this->faker->numberBetween(24, 26);           // YY: 24, 25, 26
        $semester = $this->faker->numberBetween(1, 2);         // SEM: 1 or 2
        $level = $this->faker->numberBetween(1, 4);            // LEVEL: 1-4
        $sfo = $this->faker->numberBetween(1, 3);              // SFO: 1-3
        
        $edpCode = sprintf('%s-%d%d%d%03d', $major, $year, $semester, $level, $sfo);

        return [
            'edp_code' => $edpCode,
            'subject_code' => strtoupper($this->faker->unique()->bothify('???###')),
            'description' => $this->faker->sentence(3),
            'department' => $major,
            'major' => $this->faker->randomElement(['Systems', 'Networks', 'Web']),
            'year_level' => $level,
            'section' => $this->faker->randomElement(['A', 'B', 'C']),
            'units' => $this->faker->numberBetween(1, 4),
            'duration_hours' => $this->faker->randomElement([1, 1.5, 2, 3]),
            'meetings_per_week' => $this->faker->numberBetween(1, 3),
            'type' => $this->faker->randomElement(['Major', 'Minor']),
            'subject_type' => $this->faker->randomElement(['Lecture', 'Lab', 'Practical']),
            'specialization' => $this->faker->randomElement(['IT', 'CCS', 'ACT', 'GENERAL']),
        ];
    }
}