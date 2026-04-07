<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'section_id' => SectionFactory::new(),
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
            'dropped_at' => null,
        ];
    }

    public function forStudent(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn () => ['section_id' => $section->id]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => EnrollmentStatus::Completed]);
    }

    public function dropped(): static
    {
        return $this->state(fn () => [
            'status' => EnrollmentStatus::Dropped,
            'dropped_at' => now(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn () => [
            'status' => EnrollmentStatus::Withdrawn,
            'dropped_at' => now(),
        ]);
    }
}
