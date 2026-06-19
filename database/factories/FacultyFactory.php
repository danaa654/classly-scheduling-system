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
            'employment_type' => 'Full-time',
            'faculty_scope' => 'departmental',
            'can_teach_minor' => false,
            'max_units' => 21,
            'availability' => json_encode([
                'Monday' => ['start' => '08:00', 'end' => '17:00'],
                'Tuesday' => ['start' => '08:00', 'end' => '17:00'],
                'Wednesday' => ['start' => '08:00', 'end' => '17:00'],
                'Thursday' => ['start' => '08:00', 'end' => '17:00'],
                'Friday' => ['start' => '08:00', 'end' => '17:00'],
            ]),
        ];
    }

    /**
     * Indicate that the faculty has restricted availability windows.
     */
    public function withRestrictedAvailability(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability' => json_encode([
                'Monday' => [['start' => '08:00:00', 'end' => '12:00:00']],
                'Tuesday' => [['start' => '14:00:00', 'end' => '17:00:00']],
            ]),
        ]);
    }

    /**
     * Indicate that the faculty has no availability.
     */
    public function withNoAvailability(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability' => json_encode([]),
        ]);
    }

    /**
     * Indicate that the faculty is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the faculty is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the faculty is a GenEd faculty member.
     */
    public function gened(): static
    {
        return $this->state(fn (array $attributes) => [
            'faculty_scope' => 'gened',
            'department' => null,
            'can_teach_minor' => true,
        ]);
    }
}