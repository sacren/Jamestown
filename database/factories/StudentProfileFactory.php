<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id_number' => StudentProfile::generateIdNumber(),
            'enrollment_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => StudentStatus::Active,
        ];
    }

    public function applicant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Applicant,
            'enrollment_date' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Active,
        ]);
    }

    public function graduated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Graduated,
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Withdrawn,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Suspended,
        ]);
    }
}
