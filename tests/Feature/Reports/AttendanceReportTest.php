<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access attendance report', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.attendance'))
        ->assertOk();
});

test('student cannot access attendance report', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.reports.attendance'))
        ->assertForbidden();
});

test('displays correct overall attendance stats', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $schedule = SectionSchedule::factory()->create(['section_id' => $section->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();

    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->count(7)->create(['status' => AttendanceStatus::Present]);
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->count(2)->create(['status' => AttendanceStatus::Absent]);
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->create(['status' => AttendanceStatus::Late]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.attendance')
        ->assertSee('Total Records')
        ->assertSee('Attendance Rate')
        ->assertSee('Absent Rate')
        ->assertSee('Late Rate');
});

test('term filter changes results', function () {
    $admin = User::factory()->asAdmin()->create();
    $term1 = Term::factory()->create(['is_active' => true]);
    $term2 = Term::factory()->create(['is_active' => false]);

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section1 = Section::factory()->forCourse($course)->create(['term_id' => $term1->id]);
    $section2 = Section::factory()->forCourse($course)->create(['term_id' => $term2->id, 'section_number' => '02']);
    $schedule1 = SectionSchedule::factory()->create(['section_id' => $section1->id]);
    $schedule2 = SectionSchedule::factory()->create(['section_id' => $section2->id]);

    $enrollment1 = Enrollment::factory()->forSection($section1)->create();
    $enrollment2 = Enrollment::factory()->forSection($section2)->create();

    Attendance::factory()->forEnrollment($enrollment1)->forSchedule($schedule1)->count(5)->create();
    Attendance::factory()->forEnrollment($enrollment2)->forSchedule($schedule2)->count(10)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.attendance');

    // Default active term has 5 records
    expect($component->get('overallStats')['total'])->toBe(5);

    // Switch to term2 — 10 records
    $component->set('termId', (string) $term2->id);
    expect($component->get('overallStats')['total'])->toBe(10);
});

test('section filter narrows to one section', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();

    $sectionA = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $sectionB = Section::factory()->forCourse($course)->create(['term_id' => $term->id, 'section_number' => '02']);
    $scheduleA = SectionSchedule::factory()->create(['section_id' => $sectionA->id]);
    $scheduleB = SectionSchedule::factory()->create(['section_id' => $sectionB->id]);

    $enrollmentA = Enrollment::factory()->forSection($sectionA)->create();
    $enrollmentB = Enrollment::factory()->forSection($sectionB)->create();

    Attendance::factory()->forEnrollment($enrollmentA)->forSchedule($scheduleA)->count(3)->create();
    Attendance::factory()->forEnrollment($enrollmentB)->forSchedule($scheduleB)->count(7)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.attendance')
        ->set('sectionId', (string) $sectionA->id);

    expect($component->get('overallStats')['total'])->toBe(3);
});

test('at-risk students shown when rate below 75 percent', function () {
    $admin = User::factory()->asAdmin()->create();
    $atRiskStudent = User::factory()->asStudent()->create(['name' => 'Risky Rhodes']);
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $schedule = SectionSchedule::factory()->create(['section_id' => $section->id]);
    $enrollment = Enrollment::factory()->forStudent($atRiskStudent)->forSection($section)->create();

    // 2 present, 8 absent → 20% attendance rate
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->count(2)->create(['status' => AttendanceStatus::Present]);
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->count(8)->create(['status' => AttendanceStatus::Absent]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.attendance')
        ->assertSee('Risky Rhodes')
        ->assertSee('At-Risk Students');
});

test('no at-risk students when all attendance is good', function () {
    $admin = User::factory()->asAdmin()->create();
    $goodStudent = User::factory()->asStudent()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $schedule = SectionSchedule::factory()->create(['section_id' => $section->id]);
    $enrollment = Enrollment::factory()->forStudent($goodStudent)->forSection($section)->create();

    // 10 present → 100% rate
    Attendance::factory()->forEnrollment($enrollment)->forSchedule($schedule)->count(10)->create(['status' => AttendanceStatus::Present]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.attendance')
        ->assertSee('No at-risk students');
});

test('handles no attendance records gracefully', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.attendance')
        ->assertSee('No attendance records');
});
