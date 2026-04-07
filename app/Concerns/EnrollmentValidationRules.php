<?php

namespace App\Concerns;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;

trait EnrollmentValidationRules
{
    use ChecksScheduleConflicts;

    /**
     * Validate that a student is eligible to enroll in a section.
     *
     * @return array<string, string> Error messages keyed by field name (empty if valid)
     */
    protected function validateEnrollmentEligibility(User $student, Section $section, ?int $excludeSectionId = null): array
    {
        $errors = [];

        // 1. Student must have active status
        if (! $student->studentProfile || $student->studentProfile->status !== StudentStatus::Active) {
            $errors['student_id'] = 'Student must have an active enrollment status.';
        }

        // 2. Section must be active
        if (! $section->is_active) {
            $errors['section_id'] = 'This section is not currently active.';
        }

        // 3. Check capacity
        $enrolledCount = $section->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->when($excludeSectionId, fn ($q) => $q->where('section_id', '!=', $excludeSectionId))
            ->count();

        if ($enrolledCount >= $section->max_enrollment) {
            $errors['section_id'] = 'This section has reached its maximum enrollment capacity.';
        }

        // 4. Check duplicate course in same term
        $duplicateCourse = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('status', EnrollmentStatus::Enrolled)
            ->whereHas('section', function ($query) use ($section) {
                $query->where('course_id', $section->course_id)
                    ->where('term_id', $section->term_id)
                    ->where('id', '!=', $section->id);
            })
            ->exists();

        if ($duplicateCourse) {
            $errors['section_id'] = 'Student is already enrolled in another section of this course for this term.';
        }

        // 5. Check prerequisites
        $section->load('course.prerequisites');
        foreach ($section->course->prerequisites as $prerequisite) {
            $completed = Enrollment::query()
                ->where('user_id', $student->id)
                ->where('status', EnrollmentStatus::Completed)
                ->whereHas('section', function ($query) use ($prerequisite) {
                    $query->where('course_id', $prerequisite->id);
                })
                ->exists();

            if (! $completed) {
                $errors['section_id'] = "Prerequisite not met: {$prerequisite->code} must be completed first.";
                break;
            }
        }

        // 6. Check student schedule conflict
        $section->load('schedules');
        foreach ($section->schedules as $schedule) {
            if ($this->checkStudentScheduleConflict(
                $student->id,
                $section->term_id,
                $schedule->day_of_week->value,
                $schedule->start_time,
                $schedule->end_time,
                $excludeSectionId,
            )) {
                $errors['section_id'] = 'This section conflicts with another enrolled section\'s schedule.';
                break;
            }
        }

        return $errors;
    }
}
