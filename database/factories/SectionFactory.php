<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Section> */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'course_id' => CourseFactory::new(),
            'term_id' => TermFactory::new(),
            'instructor_id' => null,
            'section_number' => '01',
            'max_enrollment' => fake()->numberBetween(20, 30),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
        ]);
    }

    public function forTerm(Term $term): static
    {
        return $this->state(fn (array $attributes) => [
            'term_id' => $term->id,
        ]);
    }

    public function withInstructor(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'instructor_id' => $user->id,
        ]);
    }
}
