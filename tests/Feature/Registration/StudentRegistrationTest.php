<?php

use App\Enums\DayOfWeek;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
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

test('student can view registration page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.sections'))
        ->assertOk();
});

test('student can view schedule page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.schedule'))
        ->assertOk();
});

test('non-student cannot access registration', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('registration.sections'))
        ->assertForbidden();
});

test('guest is redirected to login from registration', function () {
    $this->get(route('registration.sections'))
        ->assertRedirect(route('login'));
});

test('student can enroll in an available section', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $section = Section::factory()->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasNoErrors();

    expect(Enrollment::where('user_id', $student->id)->where('section_id', $section->id)->exists())->toBeTrue();
});

test('student cannot enroll outside registration period', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subMonths(2),
        'registration_end' => now()->subMonth(),
    ]);
    $section = Section::factory()->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasErrors(['section_id']);

    expect(Enrollment::where('user_id', $student->id)->where('section_id', $section->id)->exists())->toBeFalse();
});

test('student cannot enroll in full section', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $section = Section::factory()->forTerm($term)->create(['max_enrollment' => 1]);
    SectionSchedule::factory()->forSection($section)->create();
    $otherStudent = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($otherStudent)->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasErrors(['section_id']);
});

test('student cannot enroll in same course twice in same term', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $course = Course::factory()->create();
    $section1 = Section::factory()->forCourse($course)->forTerm($term)->create(['section_number' => '01']);
    $section2 = Section::factory()->forCourse($course)->forTerm($term)->create(['section_number' => '02', 'max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section1)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00',
        'end_time' => '09:00',
    ]);
    SectionSchedule::factory()->forSection($section2)->create([
        'day_of_week' => DayOfWeek::Tuesday,
        'start_time' => '08:00',
        'end_time' => '09:00',
    ]);
    Enrollment::factory()->forStudent($student)->forSection($section1)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section2->id)
        ->assertHasErrors(['section_id']);
});

test('student cannot enroll without completed prerequisites', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $program = Program::factory()->create();
    $prereq = Course::factory()->forProgram($program)->create(['code' => 'WLD-101']);
    $course = Course::factory()->forProgram($program)->create(['code' => 'WLD-201']);
    $course->prerequisites()->attach($prereq->id);

    $section = Section::factory()->forCourse($course)->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasErrors(['section_id']);
});

test('student with completed prerequisite can enroll', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $program = Program::factory()->create();
    $prereq = Course::factory()->forProgram($program)->create(['code' => 'WLD-101']);
    $course = Course::factory()->forProgram($program)->create(['code' => 'WLD-201']);
    $course->prerequisites()->attach($prereq->id);

    // Create completed enrollment for prerequisite
    $prereqSection = Section::factory()->forCourse($prereq)->create();
    Enrollment::factory()->forStudent($student)->forSection($prereqSection)->completed()->create();

    $section = Section::factory()->forCourse($course)->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Wednesday,
        'start_time' => '14:00',
        'end_time' => '15:30',
    ]);

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasNoErrors();

    expect(Enrollment::where('user_id', $student->id)->where('section_id', $section->id)->exists())->toBeTrue();
});

test('student cannot enroll with schedule conflict', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $course1 = Course::factory()->create();
    $section1 = Section::factory()->forCourse($course1)->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section1)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);
    Enrollment::factory()->forStudent($student)->forSection($section1)->create();

    $course2 = Course::factory()->create();
    $section2 = Section::factory()->forCourse($course2)->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section2)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '10:00',
        'end_time' => '11:00',
    ]);

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section2->id)
        ->assertHasErrors(['section_id']);
});

test('student must be active to enroll', function () {
    $student = User::factory()->asStudent()->create();
    $student->studentProfile->update(['status' => StudentStatus::Suspended]);

    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $section = Section::factory()->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasErrors(['student_id']);
});

test('student cannot enroll in inactive section', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $section = Section::factory()->forTerm($term)->create(['is_active' => false, 'max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.sections')
        ->set('termFilter', (string) $term->id)
        ->call('enroll', $section->id)
        ->assertHasErrors(['section_id']);
});

test('student can view enrolled sections on schedule page', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create();
    $course = Course::factory()->create(['name' => 'Intro to Welding']);
    $section = Section::factory()->forCourse($course)->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.schedule')
        ->set('termFilter', (string) $term->id)
        ->assertSee('Intro to Welding');
});

test('student can drop enrollment during registration period', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subDay(),
        'registration_end' => now()->addDay(),
    ]);
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.schedule')
        ->set('termFilter', (string) $term->id)
        ->call('dropEnrollment', $enrollment->id)
        ->assertHasNoErrors();

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Dropped);
});

test('student cannot drop enrollment after registration period', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => now()->subMonths(2),
        'registration_end' => now()->subMonth(),
    ]);
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.schedule')
        ->set('termFilter', (string) $term->id)
        ->call('dropEnrollment', $enrollment->id)
        ->assertHasErrors(['drop']);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Enrolled);
});
