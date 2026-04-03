<?php

namespace Database\Factories;

use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => StaffProfile::generateEmployeeId(),
            'department' => fake()->randomElement(['Administration', 'Admissions', 'Student Services', 'Finance']),
            'title' => fake()->jobTitle(),
        ];
    }
}
