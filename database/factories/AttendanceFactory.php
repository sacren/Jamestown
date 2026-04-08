<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SectionSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Attendance> */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'enrollment_id' => EnrollmentFactory::new(),
            'section_schedule_id' => SectionScheduleFactory::new(),
            'date' => fake()->date(),
            'status' => AttendanceStatus::Present,
            'notes' => null,
            'recorded_by' => UserFactory::new(),
        ];
    }

    public function forEnrollment(Enrollment $enrollment): static
    {
        return $this->state(fn () => ['enrollment_id' => $enrollment->id]);
    }

    public function forSchedule(SectionSchedule $schedule): static
    {
        return $this->state(fn () => ['section_schedule_id' => $schedule->id]);
    }

    public function absent(): static
    {
        return $this->state(fn () => ['status' => AttendanceStatus::Absent]);
    }

    public function late(): static
    {
        return $this->state(fn () => ['status' => AttendanceStatus::Late]);
    }

    public function excused(): static
    {
        return $this->state(fn () => ['status' => AttendanceStatus::Excused]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn () => ['date' => $date]);
    }

    public function recordedBy(User $user): static
    {
        return $this->state(fn () => ['recorded_by' => $user->id]);
    }
}
