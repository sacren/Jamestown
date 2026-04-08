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

test('admin can view enrollments listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.enrollments.index'))
        ->assertOk();
});

test('registrar can view enrollments listing page', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.enrollments.index'))
        ->assertOk();
});

test('student cannot access enrollment management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.enrollments.index'))
        ->assertForbidden();
});

test('guest is redirected to login from enrollment management', function () {
    $this->get(route('admin.enrollments.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access enrollment management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.enrollments.index'))
        ->assertOk();
});

test('admin can search enrollments by student name', function () {
    $admin = User::factory()->asAdmin()->create();
    $alice = User::factory()->asStudent()->create(['name' => 'Alice Smith']);
    $bob = User::factory()->asStudent()->create(['name' => 'Bob Jones']);
    Enrollment::factory()->forStudent($alice)->create();
    Enrollment::factory()->forStudent($bob)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->set('search', 'Alice')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

test('admin can filter enrollments by term', function () {
    $admin = User::factory()->asAdmin()->create();
    $fall = Term::factory()->create(['name' => 'Fall 2026']);
    $spring = Term::factory()->create(['name' => 'Spring 2027']);
    $program = Program::factory()->create();
    $fallCourse = Course::factory()->forProgram($program)->create(['name' => 'Fall Enrolled Course']);
    $springCourse = Course::factory()->forProgram($program)->create(['name' => 'Spring Enrolled Course']);
    $fallSection = Section::factory()->forCourse($fallCourse)->forTerm($fall)->create();
    $springSection = Section::factory()->forCourse($springCourse)->forTerm($spring)->create();
    Enrollment::factory()->forSection($fallSection)->create();
    Enrollment::factory()->forSection($springSection)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->set('termFilter', $fall->id)
        ->assertSee('Fall Enrolled Course')
        ->assertDontSee('Spring Enrolled Course');
});

test('admin can filter enrollments by status', function () {
    $admin = User::factory()->asAdmin()->create();
    $student1 = User::factory()->asStudent()->create(['name' => 'Active Enrollee']);
    $student2 = User::factory()->asStudent()->create(['name' => 'Dropped Enrollee']);
    Enrollment::factory()->forStudent($student1)->create(['status' => EnrollmentStatus::Enrolled]);
    Enrollment::factory()->forStudent($student2)->dropped()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->set('statusFilter', 'enrolled')
        ->assertSee('Active Enrollee')
        ->assertDontSee('Dropped Enrollee');
});

test('admin can view manual enrollment page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.enrollments.create'))
        ->assertOk();
});

test('admin can manually enroll a student', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $section = Section::factory()->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $student->id)
        ->set('section_id', (string) $section->id)
        ->call('enrollStudent')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.enrollments.index'));

    expect(Enrollment::where('user_id', $student->id)->where('section_id', $section->id)->exists())->toBeTrue();
});

test('manual enrollment validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', '')
        ->set('section_id', '')
        ->call('enrollStudent')
        ->assertHasErrors(['student_id', 'section_id']);
});

test('manual enrollment prevents exceeding max capacity', function () {
    $admin = User::factory()->asAdmin()->create();
    $section = Section::factory()->create(['max_enrollment' => 1]);
    SectionSchedule::factory()->forSection($section)->create();
    $existingStudent = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($existingStudent)->forSection($section)->create();

    $newStudent = User::factory()->asStudent()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $newStudent->id)
        ->set('section_id', (string) $section->id)
        ->call('enrollStudent')
        ->assertHasErrors(['section_id']);
});

test('manual enrollment prevents duplicate course in same term', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create();
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

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $student->id)
        ->set('section_id', (string) $section2->id)
        ->call('enrollStudent')
        ->assertHasErrors(['section_id']);
});

test('manual enrollment enforces prerequisites', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $prereq = Course::factory()->forProgram($program)->create(['code' => 'WLD-101']);
    $course = Course::factory()->forProgram($program)->create(['code' => 'WLD-201']);
    $course->prerequisites()->attach($prereq->id);

    $term = Term::factory()->create();
    $section = Section::factory()->forCourse($course)->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $student->id)
        ->set('section_id', (string) $section->id)
        ->call('enrollStudent')
        ->assertHasErrors(['section_id']);
});

test('manual enrollment detects student schedule conflict', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create();

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

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $student->id)
        ->set('section_id', (string) $section2->id)
        ->call('enrollStudent')
        ->assertHasErrors(['section_id']);
});

test('manual enrollment requires active student status', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $student->studentProfile->update(['status' => StudentStatus::Suspended]);

    $section = Section::factory()->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $student->id)
        ->set('section_id', (string) $section->id)
        ->call('enrollStudent')
        ->assertHasErrors(['student_id']);
});

test('admin can enroll outside registration period', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create([
        'registration_start' => '2025-01-01',
        'registration_end' => '2025-01-15',
    ]);
    $section = Section::factory()->forTerm($term)->create(['max_enrollment' => 25]);
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.create')
        ->set('student_id', (string) $student->id)
        ->set('section_id', (string) $section->id)
        ->call('enrollStudent')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.enrollments.index'));
});

test('admin can drop an enrollment', function () {
    $admin = User::factory()->asAdmin()->create();
    $enrollment = Enrollment::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->call('dropEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Dropped);
    expect($enrollment->dropped_at)->not->toBeNull();
});

test('admin can withdraw an enrollment', function () {
    $admin = User::factory()->asAdmin()->create();
    $enrollment = Enrollment::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->call('withdrawEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Withdrawn);
    expect($enrollment->dropped_at)->not->toBeNull();
});
