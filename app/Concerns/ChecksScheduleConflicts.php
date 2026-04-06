<?php

namespace App\Concerns;

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
}
