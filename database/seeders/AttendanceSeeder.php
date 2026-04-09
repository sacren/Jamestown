<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\EnrollmentStatus;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Seed attendance records for currently enrolled students in past meetings.
     */
    public function run(): void
    {
        $recorder = User::where('email', 'instructor@example.com')->first()
            ?? User::where('email', 'admin@example.com')->first();

        if (! $recorder) {
            return;
        }

        $enrollments = Enrollment::query()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('section.term', 'section.schedules')
            ->get();

        foreach ($enrollments as $enrollment) {
            $section = $enrollment->section;
            $term = $section->term;

            $start = $term->start_date;
            $end = min(now()->toDateString(), $term->end_date->toDateString());

            if ($start->gt($end)) {
                continue;
            }

            $period = CarbonPeriod::create($start, $end);

            foreach ($period as $date) {
                $dayOfWeek = DayOfWeek::tryFrom(strtolower($date->format('l')));
                if (! $dayOfWeek) {
                    continue;
                }

                $schedule = $section->schedules->first(fn ($s) => $s->day_of_week === $dayOfWeek);
                if (! $schedule) {
                    continue;
                }

                Attendance::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'section_schedule_id' => $schedule->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => $this->randomStatus(),
                        'recorded_by' => $recorder->id,
                    ],
                );
            }
        }
    }

    private function randomStatus(): AttendanceStatus
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 85 => AttendanceStatus::Present,
            $roll <= 90 => AttendanceStatus::Late,
            $roll <= 95 => AttendanceStatus::Absent,
            default => AttendanceStatus::Excused,
        };
    }
}
