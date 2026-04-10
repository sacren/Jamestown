<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Seed realistic grades for each enrolled student across all assessments in their section.
     */
    public function run(): void
    {
        $fallbackGrader = User::where('email', 'admin@example.com')->first();

        $enrollments = Enrollment::query()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('section.instructor')
            ->get();

        foreach ($enrollments as $enrollment) {
            $assessments = Assessment::query()
                ->where('section_id', $enrollment->section_id)
                ->get();

            $grader = $enrollment->section->instructor ?? $fallbackGrader;
            if (! $grader) {
                continue;
            }

            foreach ($assessments as $assessment) {
                $min = (int) round($assessment->max_points * 0.5);
                $max = $assessment->max_points;

                Grade::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'assessment_id' => $assessment->id,
                    ],
                    [
                        'score' => random_int($min * 100, $max * 100) / 100,
                        'graded_by' => $grader->id,
                    ],
                );
            }
        }
    }
}
