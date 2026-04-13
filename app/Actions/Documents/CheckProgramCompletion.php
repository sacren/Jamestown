<?php

namespace App\Actions\Documents;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Collection;

class CheckProgramCompletion
{
    /**
     * @return array{eligible: bool, total_courses: int, completed_courses: Collection<int, Course>, in_progress_courses: Collection<int, Course>, remaining_courses: Collection<int, Course>}
     */
    public function handle(User $student, Program $program): array
    {
        $allCourses = $program->courses;

        $enrollments = Enrollment::query()
            ->where('user_id', $student->id)
            ->whereHas('section.course', fn ($q) => $q->where('program_id', $program->id))
            ->with('section.course')
            ->get();

        $completedCourseIds = $enrollments
            ->filter(fn (Enrollment $e) => $e->status === EnrollmentStatus::Completed)
            ->pluck('section.course.id')
            ->unique();

        $inProgressCourseIds = $enrollments
            ->filter(fn (Enrollment $e) => $e->status === EnrollmentStatus::Enrolled)
            ->pluck('section.course.id')
            ->unique()
            ->diff($completedCourseIds);

        $completedCourses = $allCourses->whereIn('id', $completedCourseIds)->values();
        $inProgressCourses = $allCourses->whereIn('id', $inProgressCourseIds)->values();
        $remainingCourses = $allCourses
            ->whereNotIn('id', $completedCourseIds->merge($inProgressCourseIds))
            ->values();

        return [
            'eligible' => $remainingCourses->isEmpty() && $inProgressCourses->isEmpty() && $completedCourses->count() === $allCourses->count(),
            'total_courses' => $allCourses->count(),
            'completed_courses' => $completedCourses,
            'in_progress_courses' => $inProgressCourses,
            'remaining_courses' => $remainingCourses,
        ];
    }
}
