<?php

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * Helper: build a section with a Monday schedule, an enrolled student,
 * and a term whose date range covers a known past Monday.
 */
function buildAttendanceFixture(?User $instructor = null): array
{
    $instructor ??= User::factory()->asInstructor()->create();
    // 2026-04-06 is a Monday
    $monday = '2026-04-06';
    $term = Term::factory()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->withInstructor($instructor)->forTerm($term)->create();
    $schedule = SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();

    return compact('instructor', 'section', 'schedule', 'student', 'enrollment', 'monday', 'term');
}

test('instructor can view attendance recording page for own section', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor'])
        ->get(route('instructor.attendance.record', $f['section']))
        ->assertOk();
});

test('instructor cannot view attendance page for another instructors section', function () {
    $f = buildAttendanceFixture();
    $other = User::factory()->asInstructor()->create();

    $this->actingAs($other)
        ->get(route('instructor.attendance.record', $f['section']))
        ->assertForbidden();
});

test('guest is redirected to login from instructor attendance page', function () {
    $f = buildAttendanceFixture();

    $this->get(route('instructor.attendance.record', $f['section']))
        ->assertRedirect(route('login'));
});

test('student cannot access instructor attendance recording page', function () {
    $f = buildAttendanceFixture();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('instructor.attendance.record', $f['section']))
        ->assertForbidden();
});

test('attendance page shows enrolled students', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->assertSee($f['student']->name);
});

test('attendance page defaults all students to present', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    $component = Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']]);

    expect($component->get('attendances')[$f['enrollment']->id]['status'])
        ->toBe(AttendanceStatus::Present->value);
});

test('instructor can save attendance for a date', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->set('attendances.'.$f['enrollment']->id.'.status', AttendanceStatus::Absent->value)
        ->call('saveAttendance');

    $record = Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect($record)->not->toBeNull();
    expect($record->status)->toBe(AttendanceStatus::Absent);
    expect($record->date->format('Y-m-d'))->toBe($f['monday']);
});

test('attendance is saved with correct section schedule id', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->call('saveAttendance');

    $record = Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect($record->section_schedule_id)->toBe($f['schedule']->id);
});

test('instructor can update existing attendance records', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->set('attendances.'.$f['enrollment']->id.'.status', AttendanceStatus::Present->value)
        ->call('saveAttendance');

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->set('attendances.'.$f['enrollment']->id.'.status', AttendanceStatus::Late->value)
        ->call('saveAttendance');

    expect(Attendance::query()->where('enrollment_id', $f['enrollment']->id)->count())->toBe(1);
    expect(Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first()->status)
        ->toBe(AttendanceStatus::Late);
});

test('attendance date must be within term date range', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    $component = Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', '2025-04-06') // before term
        ->call('saveAttendance');

    expect($component->get('dateErrors'))->not->toBeEmpty();
    expect(Attendance::query()->where('enrollment_id', $f['enrollment']->id)->count())->toBe(0);
});

test('attendance date cannot be in the future', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    // Find next Monday from now
    $futureMonday = Carbon::now()->next(Carbon::MONDAY)->addWeek()->toDateString();

    $component = Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $futureMonday)
        ->call('saveAttendance');

    expect($component->get('dateErrors'))->not->toBeEmpty();
});

test('attendance date must match a scheduled day of week', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    // 2026-04-07 is a Tuesday — section meets only Monday
    $component = Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', '2026-04-07')
        ->call('saveAttendance');

    expect($component->get('dateErrors'))->not->toBeEmpty();
});

test('attendance records the authenticated user as recorded_by', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->call('saveAttendance');

    $record = Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect($record->recorded_by)->toBe($f['instructor']->id);
});

test('dropped students do not appear in attendance form', function () {
    $f = buildAttendanceFixture();

    $droppedStudent = User::factory()->asStudent()->create(['name' => 'Dropped Student Name']);
    Enrollment::factory()
        ->forStudent($droppedStudent)
        ->forSection($f['section'])
        ->dropped()
        ->create();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->assertDontSee('Dropped Student Name');
});

test('instructor can add notes to attendance records', function () {
    $f = buildAttendanceFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->set('attendances.'.$f['enrollment']->id.'.notes', 'Left class early')
        ->call('saveAttendance');

    $record = Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect($record->notes)->toBe('Left class early');
});
