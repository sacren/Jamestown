<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Grade> */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'enrollment_id' => EnrollmentFactory::new(),
            'assessment_id' => AssessmentFactory::new(),
            'score' => fake()->randomFloat(2, 0, 100),
            'letter_grade' => null,
            'notes' => null,
            'graded_by' => UserFactory::new(),
        ];
    }

    public function forEnrollment(Enrollment $enrollment): static
    {
        return $this->state(fn () => ['enrollment_id' => $enrollment->id]);
    }

    public function forAssessment(Assessment $assessment): static
    {
        return $this->state(fn () => ['assessment_id' => $assessment->id]);
    }

    public function gradedBy(User $user): static
    {
        return $this->state(fn () => ['graded_by' => $user->id]);
    }

    public function withScore(float $score): static
    {
        return $this->state(fn () => ['score' => $score]);
    }

    public function withLetterGrade(string $grade): static
    {
        return $this->state(fn () => ['letter_grade' => $grade]);
    }

    public function perfect(): static
    {
        return $this->state(fn () => ['score' => 100]);
    }

    public function zero(): static
    {
        return $this->state(fn () => ['score' => 0]);
    }
}
