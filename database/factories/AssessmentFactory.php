<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assessment> */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'section_id' => SectionFactory::new(),
            'title' => fake()->sentence(3),
            'type' => AssessmentType::Assignment,
            'description' => null,
            'max_points' => 100,
            'due_date' => null,
            'sort_order' => 0,
        ];
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn () => ['section_id' => $section->id]);
    }

    public function exam(): static
    {
        return $this->state(fn () => ['type' => AssessmentType::Exam, 'max_points' => 100]);
    }

    public function quiz(): static
    {
        return $this->state(fn () => ['type' => AssessmentType::Quiz, 'max_points' => 25]);
    }

    public function project(): static
    {
        return $this->state(fn () => ['type' => AssessmentType::Project, 'max_points' => 50]);
    }

    public function lab(): static
    {
        return $this->state(fn () => ['type' => AssessmentType::Lab, 'max_points' => 30]);
    }

    public function presentation(): static
    {
        return $this->state(fn () => ['type' => AssessmentType::Presentation, 'max_points' => 50]);
    }

    public function final(): static
    {
        return $this->state(fn () => ['type' => AssessmentType::Final, 'max_points' => 200]);
    }

    public function withDueDate(string $date): static
    {
        return $this->state(fn () => ['due_date' => $date]);
    }

    public function withMaxPoints(int $points): static
    {
        return $this->state(fn () => ['max_points' => $points]);
    }

    public function withSortOrder(int $order): static
    {
        return $this->state(fn () => ['sort_order' => $order]);
    }
}
