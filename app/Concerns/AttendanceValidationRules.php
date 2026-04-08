<?php

namespace App\Concerns;

use App\Enums\DayOfWeek;
use App\Enums\EnrollmentStatus;
use App\Models\Section;
use App\Models\SectionSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait AttendanceValidationRules
{
    /**
     * Validate that a date is valid for recording attendance for a section.
     *
     * @return array<int, string> Error messages (empty if valid)
     */
    protected function validateAttendanceDate(Section $section, string $date): array
    {
        $errors = [];

        $dateCarbon = Carbon::parse($date);
        $section->load('term', 'schedules');

        // 1. Date must be within the section's term date range
        if ($dateCarbon->lt($section->term->start_date) || $dateCarbon->gt($section->term->end_date)) {
            $errors[] = 'The date must be within the term period ('.$section->term->start_date->format('M j, Y').' to '.$section->term->end_date->format('M j, Y').').';
        }

        // 2. Date's day of week must match one of the section's schedules
        $dayOfWeek = DayOfWeek::tryFrom(strtolower($dateCarbon->format('l')));
        $scheduledDays = $section->schedules->pluck('day_of_week')->toArray();

        if (! in_array($dayOfWeek, $scheduledDays)) {
            $dayNames = collect($scheduledDays)->map(fn (DayOfWeek $d) => $d->name)->join(', ');
            $errors[] = 'This section does not meet on '.$dateCarbon->format('l').'. Scheduled days: '.$dayNames.'.';
        }

        // 3. Date must not be in the future
        if ($dateCarbon->isFuture()) {
            $errors[] = 'Attendance cannot be recorded for a future date.';
        }

        return $errors;
    }

    /**
     * Get the section schedule matching the given date's day of week.
     */
    protected function getScheduleForDate(Section $section, string $date): ?SectionSchedule
    {
        $dayOfWeek = DayOfWeek::tryFrom(strtolower(Carbon::parse($date)->format('l')));

        $section->loadMissing('schedules');

        return $section->schedules->first(fn (SectionSchedule $s) => $s->day_of_week === $dayOfWeek);
    }

    /**
     * Get enrolled students for a section with their enrollment IDs.
     */
    protected function getEnrolledStudentsForSection(Section $section): Collection
    {
        return $section->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('student.studentProfile')
            ->get();
    }
}
