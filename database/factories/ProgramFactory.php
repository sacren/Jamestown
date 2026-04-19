<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Technology';

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'duration_weeks' => fake()->numberBetween(16, 52),
            'total_credits_required' => fake()->numberBetween(30, 60),
            'tuition_cost' => fake()->randomFloat(2, 5000, 25000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
