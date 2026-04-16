<?php

namespace Database\Factories;

use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Term> */
class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2025, 2028);
        $semester = fake()->randomElement(['Fall', 'Spring', 'Summer']);
        $code = match ($semester) {
            'Fall' => 'FA',
            'Spring' => 'SP',
            'Summer' => 'SU',
        };
        $startDate = match ($semester) {
            'Fall' => Carbon::create($year, 9, 1),
            'Spring' => Carbon::create($year, 1, 15),
            'Summer' => Carbon::create($year, 6, 1),
        };

        return [
            'name' => $semester.' '.$year,
            'code' => $code.$year.fake()->unique()->numerify('##'),
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addWeeks(16),
            'registration_start' => $startDate->copy()->subWeeks(4),
            'registration_end' => $startDate->copy()->subWeek(),
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
