<?php

namespace App\Concerns;

use App\Enums\EnrollmentStatus;
use App\Models\SectionSchedule;

trait ChecksScheduleConflicts
{
    protected function checkRoomConflict(
        int $termId,
        int $roomId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeSectionId = null,
    ): bool {
        return SectionSchedule::query()
            ->whereHas('section', function ($query) use ($termId, $excludeSectionId) {
                $query->where('term_id', $termId)
                    ->where('is_active', true);
                if ($excludeSectionId) {
                    $query->where('id', '!=', $excludeSectionId);
                }
            })
            ->where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    protected function checkInstructorConflict(
        int $termId,
        int $instructorId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeSectionId = null,
    ): bool {
        return SectionSchedule::query()
            ->whereHas('section', function ($query) use ($termId, $instructorId, $excludeSectionId) {
                $query->where('term_id', $termId)
                    ->where('instructor_id', $instructorId)
                    ->where('is_active', true);
                if ($excludeSectionId) {
                    $query->where('id', '!=', $excludeSectionId);
                }
            })
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    protected function checkStudentScheduleConflict(
        int $userId,
        int $termId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeSectionId = null,
    ): bool {
        return SectionSchedule::query()
            ->whereHas('section', function ($query) use ($userId, $termId, $excludeSectionId) {
                $query->where('term_id', $termId)
                    ->whereHas('enrollments', function ($eq) use ($userId) {
                        $eq->where('user_id', $userId)
                            ->where('status', EnrollmentStatus::Enrolled);
                    });
                if ($excludeSectionId) {
                    $query->where('id', '!=', $excludeSectionId);
                }
            })
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }
}
