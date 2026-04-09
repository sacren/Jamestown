<?php

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function buildAdminAttendanceFixture(): array
{
    $term = Term::factory()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->forTerm($term)->create();
    $schedule = SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $monday = '2026-04-06';

    return compact('term', 'section', 'schedule', 'student', 'enrollment', 'monday');
}

test('admin can view attendance index page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk();
});

test('super admin can access attendance index page', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.attendance.index'))
        ->assertOk();
});

test('registrar cannot access attendance management', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.attendance.index'))
        ->assertForbidden();
});

test('student cannot access attendance management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.attendance.index'))
        ->assertForbidden();
});

test('instructor can access admin attendance pages via manage-attendance permission', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.attendance.index'))
        ->assertOk();
});

test('guest is redirected to login from admin attendance', function () {
    $this->get(route('admin.attendance.index'))
        ->assertRedirect(route('login'));
});

test('admin can search sections by course name', function () {
    $admin = User::factory()->asAdmin()->create();
    $welding = Course::factory()->create(['name' => 'Welding Fundamentals', 'code' => 'WLD-100']);
    $hvac = Course::factory()->create(['name' => 'HVAC Basics', 'code' => 'HVC-100']);
    Section::factory()->forCourse($welding)->create();
    Section::factory()->forCourse($hvac)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.attendance.index')
        ->set('search', 'Welding')
        ->assertSee('Welding Fundamentals')
        ->assertDontSee('HVAC Basics');
});

test('admin can filter sections by term', function () {
    $admin = User::factory()->asAdmin()->create();
    $fall = Term::factory()->create();
    $spring = Term::factory()->create();
    $fallCourse = Course::factory()->create(['name' => 'Fall Filtered Course']);
    $springCourse = Course::factory()->create(['name' => 'Spring Filtered Course']);
    Section::factory()->forCourse($fallCourse)->forTerm($fall)->create();
    Section::factory()->forCourse($springCourse)->forTerm($spring)->create();

    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.attendance.index')
        ->set('termFilter', (string) $fall->id);

    expect($component->instance()->sections->total())->toBe(1);
});

test('admin can view attendance recording page for any section', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminAttendanceFixture();

    $this->actingAs($admin)
        ->get(route('admin.attendance.record', $f['section']))
        ->assertOk();
});

test('admin can save attendance for any section', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminAttendanceFixture();

    $this->actingAs($admin);

    Livewire::test('pages::admin.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->set('attendances.'.$f['enrollment']->id.'.status', AttendanceStatus::Late->value)
        ->call('saveAttendance');

    $record = Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect($record)->not->toBeNull();
    expect($record->status)->toBe(AttendanceStatus::Late);
    expect($record->recorded_by)->toBe($admin->id);
});

test('admin can update existing attendance records', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminAttendanceFixture();

    $this->actingAs($admin);

    Livewire::test('pages::admin.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->call('saveAttendance');

    Livewire::test('pages::admin.attendance.record', ['section' => $f['section']])
        ->set('date', $f['monday'])
        ->set('attendances.'.$f['enrollment']->id.'.status', AttendanceStatus::Excused->value)
        ->call('saveAttendance');

    expect(Attendance::query()->where('enrollment_id', $f['enrollment']->id)->count())->toBe(1);
    expect(Attendance::query()->where('enrollment_id', $f['enrollment']->id)->first()->status)
        ->toBe(AttendanceStatus::Excused);
});

test('attendance date validation applies for admin', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminAttendanceFixture();

    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.attendance.record', ['section' => $f['section']])
        ->set('date', '2026-04-07') // Tuesday — section meets only Monday
        ->call('saveAttendance');

    expect($component->get('dateErrors'))->not->toBeEmpty();
    expect(Attendance::query()->where('enrollment_id', $f['enrollment']->id)->count())->toBe(0);
});
