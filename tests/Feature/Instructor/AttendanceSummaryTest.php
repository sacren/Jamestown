<?php

use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('instructor can view attendance summary for own section', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();

    $this->actingAs($instructor)
        ->get(route('instructor.attendance.summary', $section))
        ->assertOk();
});

test('instructor cannot view summary for another instructors section', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $other = User::factory()->asInstructor()->create();

    $this->actingAs($other)
        ->get(route('instructor.attendance.summary', $section))
        ->assertForbidden();
});

test('summary shows correct attendance counts per student', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $schedule = SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
    ]);
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();

    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->onDate('2026-03-02')->create();
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->onDate('2026-03-09')->absent()->create();
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->onDate('2026-03-16')->late()->create();

    $this->actingAs($instructor);

    $component = Livewire::test('pages::instructor.attendance.summary', ['section' => $section]);
    $row = $component->instance()->students->first();

    expect($row->total)->toBe(3);
    expect($row->present)->toBe(1);
    expect($row->absent)->toBe(1);
    expect($row->late)->toBe(1);
});

test('summary calculates attendance rate percentage correctly', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $schedule = SectionSchedule::factory()->forSection($section)->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();

    // 8 present, 2 absent → 80%
    Attendance::factory()->count(8)->forEnrollment($enrollment)->forSchedule($schedule)
        ->sequence(fn ($s) => ['date' => '2026-03-'.str_pad($s->index + 1, 2, '0', STR_PAD_LEFT)])
        ->create();
    Attendance::factory()->count(2)->forEnrollment($enrollment)->forSchedule($schedule)->absent()
        ->sequence(fn ($s) => ['date' => '2026-03-'.($s->index + 20)])
        ->create();

    $this->actingAs($instructor);

    $component = Livewire::test('pages::instructor.attendance.summary', ['section' => $section]);
    $row = $component->instance()->students->first();

    expect($row->rate)->toBe(80.0);
});

test('guest is redirected to login from summary', function () {
    $section = Section::factory()->create();

    $this->get(route('instructor.attendance.summary', $section))
        ->assertRedirect(route('login'));
});

test('student cannot access attendance summary', function () {
    $section = Section::factory()->create();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('instructor.attendance.summary', $section))
        ->assertForbidden();
});
