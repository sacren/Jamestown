<?php

namespace Database\Factories;

use App\Models\InstructorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorProfile>
 */
class InstructorProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => InstructorProfile::generateEmployeeId(),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'specializations' => [fake()->word(), fake()->word()],
            'qualifications' => [fake()->sentence(), fake()->sentence()],
            'bio' => fake()->paragraph(),
        ];
    }
}
