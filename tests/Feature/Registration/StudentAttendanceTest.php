<?php

use App\Models\Attendance;
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

test('student can view their attendance page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.attendance'))
        ->assertOk();
});

test('student only sees their own attendance records', function () {
    $student = User::factory()->asStudent()->create();
    $other = User::factory()->asStudent()->create();

    $myEnrollment = Enrollment::factory()->forStudent($student)->create();
    $otherEnrollment = Enrollment::factory()->forStudent($other)->create();

    Attendance::factory()->forEnrollment($myEnrollment)->create(['notes' => 'My note marker']);
    Attendance::factory()->forEnrollment($otherEnrollment)->create(['notes' => 'Other note marker']);

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.attendance');

    expect($component->instance()->attendanceRecords)->toHaveCount(1);
    expect($component->instance()->attendanceRecords->first()->notes)->toBe('My note marker');
});

test('guest is redirected to login from student attendance', function () {
    $this->get(route('registration.attendance'))
        ->assertRedirect(route('login'));
});

test('admin cannot access student attendance page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('registration.attendance'))
        ->assertForbidden();
});

test('student can filter attendance by term', function () {
    $student = User::factory()->asStudent()->create();
    $fall = Term::factory()->create();
    $spring = Term::factory()->create();

    $fallSection = Section::factory()->forTerm($fall)->create();
    $springSection = Section::factory()->forTerm($spring)->create();

    $fallEnrollment = Enrollment::factory()->forStudent($student)->forSection($fallSection)->create();
    $springEnrollment = Enrollment::factory()->forStudent($student)->forSection($springSection)->create();

    Attendance::factory()->forEnrollment($fallEnrollment)->create();
    Attendance::factory()->forEnrollment($springEnrollment)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.attendance')
        ->set('termFilter', (string) $fall->id);

    expect($component->instance()->attendanceRecords)->toHaveCount(1);
});

test('student can filter attendance by section', function () {
    $student = User::factory()->asStudent()->create();
    $sectionA = Section::factory()->create();
    $sectionB = Section::factory()->create();

    $enrollmentA = Enrollment::factory()->forStudent($student)->forSection($sectionA)->create();
    $enrollmentB = Enrollment::factory()->forStudent($student)->forSection($sectionB)->create();

    Attendance::factory()->forEnrollment($enrollmentA)->create();
    Attendance::factory()->forEnrollment($enrollmentB)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.attendance')
        ->set('sectionFilter', (string) $sectionA->id);

    expect($component->instance()->attendanceRecords)->toHaveCount(1);
});

test('attendance page shows summary statistics', function () {
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->create();

    Attendance::factory()->count(8)->forEnrollment($enrollment)
        ->sequence(fn ($s) => ['date' => '2026-03-'.str_pad($s->index + 1, 2, '0', STR_PAD_LEFT), 'section_schedule_id' => SectionSchedule::factory()])
        ->create();
    Attendance::factory()->count(2)->forEnrollment($enrollment)->absent()
        ->sequence(fn ($s) => ['date' => '2026-03-'.($s->index + 20), 'section_schedule_id' => SectionSchedule::factory()])
        ->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.attendance');
    $summary = $component->instance()->summary->first();

    expect($summary->total)->toBe(10);
    expect($summary->rate)->toBe(80.0);
});

test('student with no enrollments sees empty state', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.attendance')
        ->assertSee(__('No enrollments'));
});
