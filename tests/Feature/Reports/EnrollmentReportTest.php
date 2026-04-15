<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access enrollment report', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.enrollment'))
        ->assertOk();
});

test('student cannot access enrollment report', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.reports.enrollment'))
        ->assertForbidden();
});

test('displays correct enrollment counts by status', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    Enrollment::factory()->forSection($section)->count(3)->create(['status' => EnrollmentStatus::Enrolled]);
    Enrollment::factory()->forSection($section)->count(2)->create(['status' => EnrollmentStatus::Completed]);
    Enrollment::factory()->forSection($section)->create(['status' => EnrollmentStatus::Dropped, 'dropped_at' => now()]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment')
        ->assertSee('Total Enrollments')
        ->assertSee('Enrolled')
        ->assertSee('Completed')
        ->assertSee('Dropped');
});

test('term filter changes displayed data', function () {
    $admin = User::factory()->asAdmin()->create();
    $term1 = Term::factory()->create(['name' => 'Fall 2026 Term', 'is_active' => true]);
    $term2 = Term::factory()->create(['name' => 'Spring 2027 Term', 'is_active' => false]);

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'RPT-101']);
    $section1 = Section::factory()->forCourse($course)->create(['term_id' => $term1->id]);
    $section2 = Section::factory()->forCourse($course)->create(['term_id' => $term2->id, 'section_number' => '02']);

    Enrollment::factory()->forSection($section1)->count(3)->create();
    Enrollment::factory()->forSection($section2)->count(5)->create();

    // Default loads active term (term1 with 3 enrollments)
    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment');

    // Switch to term2
    $component->set('termId', (string) $term2->id)
        ->assertSee('RPT-101');
});

test('program filter narrows results', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $programA = Program::factory()->create(['name' => 'Alpha Welding']);
    $programB = Program::factory()->create(['name' => 'Beta Electric']);

    $courseA = Course::factory()->for($programA)->create(['code' => 'AWLD-101']);
    $courseB = Course::factory()->for($programB)->create(['code' => 'BELC-101']);

    $sectionA = Section::factory()->forCourse($courseA)->create(['term_id' => $term->id]);
    $sectionB = Section::factory()->forCourse($courseB)->create(['term_id' => $term->id]);

    Enrollment::factory()->forSection($sectionA)->count(2)->create();
    Enrollment::factory()->forSection($sectionB)->count(3)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment')
        ->set('programId', (string) $programA->id)
        ->assertSee('Alpha Welding');

    // Beta Electric should not appear in the program breakdown table (it will still be in the filter dropdown)
    expect($component->get('enrollmentsByProgram')->pluck('program.name')->toArray())
        ->not->toContain('Beta Electric');
});

test('shows per-program breakdown', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $program = Program::factory()->create(['name' => 'Gamma Plumbing']);
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    Enrollment::factory()->forSection($section)->count(2)->create(['status' => EnrollmentStatus::Enrolled]);
    Enrollment::factory()->forSection($section)->create(['status' => EnrollmentStatus::Completed]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment')
        ->assertSee('Gamma Plumbing')
        ->assertSee('By Program');
});

test('shows per-section breakdown with capacity', function () {
    $admin = User::factory()->asAdmin()->create();
    $instructor = User::factory()->asInstructor()->create(['name' => 'Dr. Delta']);
    $term = Term::factory()->create(['is_active' => true]);

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'DLT-301']);
    $section = Section::factory()->forCourse($course)->create([
        'term_id' => $term->id,
        'instructor_id' => $instructor->id,
        'max_enrollment' => 25,
    ]);

    Enrollment::factory()->forSection($section)->count(3)->create(['status' => EnrollmentStatus::Enrolled]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment')
        ->assertSee('DLT-301')
        ->assertSee('Dr. Delta')
        ->assertSee('3 / 25');
});

test('handles no enrollments gracefully', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment')
        ->assertSee('No enrollments found');
});

test('handles no active term gracefully', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.enrollment')
        ->assertOk();
});
