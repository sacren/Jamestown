<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SectionSchedule;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('attendance has fillable attributes', function () {
    $enrollment = Enrollment::factory()->create();
    $schedule = SectionSchedule::factory()->create();
    $recorder = User::factory()->asInstructor()->create();

    $attendance = Attendance::create([
        'enrollment_id' => $enrollment->id,
        'section_schedule_id' => $schedule->id,
        'date' => '2026-04-08',
        'status' => AttendanceStatus::Present,
        'notes' => 'On time',
        'recorded_by' => $recorder->id,
    ]);

    expect($attendance->enrollment_id)->toBe($enrollment->id);
    expect($attendance->section_schedule_id)->toBe($schedule->id);
    expect($attendance->status)->toBe(AttendanceStatus::Present);
    expect($attendance->notes)->toBe('On time');
});

test('attendance belongs to an enrollment', function () {
    $enrollment = Enrollment::factory()->create();
    $attendance = Attendance::factory()->forEnrollment($enrollment)->create();

    expect($attendance->enrollment)->toBeInstanceOf(Enrollment::class);
    expect($attendance->enrollment->id)->toBe($enrollment->id);
});

test('attendance belongs to a section schedule', function () {
    $schedule = SectionSchedule::factory()->create();
    $attendance = Attendance::factory()->forSchedule($schedule)->create();

    expect($attendance->sectionSchedule)->toBeInstanceOf(SectionSchedule::class);
    expect($attendance->sectionSchedule->id)->toBe($schedule->id);
});

test('attendance belongs to a recorder user', function () {
    $recorder = User::factory()->asInstructor()->create();
    $attendance = Attendance::factory()->recordedBy($recorder)->create();

    expect($attendance->recorder)->toBeInstanceOf(User::class);
    expect($attendance->recorder->id)->toBe($recorder->id);
});

test('attendance casts status to AttendanceStatus enum', function () {
    $attendance = Attendance::factory()->absent()->create();

    expect($attendance->status)->toBeInstanceOf(AttendanceStatus::class);
    expect($attendance->status)->toBe(AttendanceStatus::Absent);
});

test('attendance casts date to Carbon date', function () {
    $attendance = Attendance::factory()->onDate('2026-04-08')->create();

    expect($attendance->date)->toBeInstanceOf(CarbonInterface::class);
    expect($attendance->date->format('Y-m-d'))->toBe('2026-04-08');
});

test('attendance enforces unique constraint on enrollment, schedule, date', function () {
    $enrollment = Enrollment::factory()->create();
    $schedule = SectionSchedule::factory()->create();

    Attendance::factory()
        ->forEnrollment($enrollment)
        ->forSchedule($schedule)
        ->onDate('2026-04-08')
        ->create();

    expect(fn () => Attendance::factory()
        ->forEnrollment($enrollment)
        ->forSchedule($schedule)
        ->onDate('2026-04-08')
        ->create()
    )->toThrow(QueryException::class);
});

test('enrollment has many attendances relationship', function () {
    $enrollment = Enrollment::factory()->create();
    $schedule = SectionSchedule::factory()->create();

    Attendance::factory()
        ->count(3)
        ->forEnrollment($enrollment)
        ->forSchedule($schedule)
        ->sequence(
            ['date' => '2026-04-06'],
            ['date' => '2026-04-13'],
            ['date' => '2026-04-20'],
        )
        ->create();

    expect($enrollment->attendances)->toHaveCount(3);
});
