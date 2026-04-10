<?php

namespace App\Concerns;

use App\Enums\EnrollmentStatus;
use App\Models\Assessment;
use App\Models\Section;
use Illuminate\Support\Collection;

trait GradeValidationRules
{
    /**
     * Validate that a score is valid for a given assessment.
     *
     * @return array<int, string> Error messages (empty if valid)
     */
    protected function validateScore(Assessment $assessment, float $score): array
    {
        $errors = [];

        if ($score < 0) {
            $errors[] = 'Score cannot be negative.';
        }

        if ($score > $assessment->max_points) {
            $errors[] = 'Score cannot exceed the maximum points ('.$assessment->max_points.').';
        }

        return $errors;
    }

    /**
     * Validate that an assessment belongs to the given section.
     *
     * @return array<int, string> Error messages (empty if valid)
     */
    protected function validateAssessmentBelongsToSection(Assessment $assessment, Section $section): array
    {
        if ($assessment->section_id !== $section->id) {
            return ['The assessment does not belong to this section.'];
        }

        return [];
    }

    /**
     * Get enrolled students for grading with their enrollment IDs.
     */
    protected function getEnrolledStudentsForGrading(Section $section): Collection
    {
        return $section->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('student.studentProfile')
            ->get();
    }
}
