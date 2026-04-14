<?php

use App\Enums\AnnouncementAudience;
use App\Enums\EnrollmentStatus;
use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('authenticated student can access dashboard', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk();
});

test('authenticated instructor can access dashboard', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertOk();
});

test('authenticated admin can access dashboard', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('student sees their enrolled courses', function () {
    $student = User::factory()->asStudent()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'WLD-101']);
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    Enrollment::factory()->forStudent($student)->forSection($section)->create(['status' => EnrollmentStatus::Enrolled]);

    $other = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($other)->forSection($section)->create(['status' => EnrollmentStatus::Enrolled]);

    Livewire::actingAs($student)
        ->test('pages::dashboard')
        ->assertSee('WLD-101');
});

test('student sees recent grades', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'HVAC-201']);
    $section = Section::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $assessment = Assessment::factory()->for($section)->create(['title' => 'Final Exam', 'max_points' => 100]);
    Grade::factory()->create(['enrollment_id' => $enrollment->id, 'assessment_id' => $assessment->id, 'score' => 92]);

    Livewire::actingAs($student)
        ->test('pages::dashboard')
        ->assertSee('HVAC-201')
        ->assertSee('Final Exam');
});

test('student sees announcements targeted to students or all', function () {
    $author = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();

    Announcement::factory()->forAuthor($author)->forAudience(AnnouncementAudience::All)->create(['title' => 'Welcome Everyone']);
    Announcement::factory()->forAuthor($author)->forAudience(AnnouncementAudience::Students)->create(['title' => 'Student Info']);
    Announcement::factory()->forAuthor($author)->forAudience(AnnouncementAudience::Instructors)->create(['title' => 'Instructor Only']);

    Livewire::actingAs($student)
        ->test('pages::dashboard')
        ->assertSee('Welcome Everyone')
        ->assertSee('Student Info')
        ->assertDontSee('Instructor Only');
});

test('instructor sees their assigned sections', function () {
    $instructor = User::factory()->asInstructor()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'PLMB-101']);
    Section::factory()->forCourse($course)->create(['term_id' => $term->id, 'instructor_id' => $instructor->id]);

    Livewire::actingAs($instructor)
        ->test('pages::dashboard')
        ->assertSee('PLMB-101');
});

test('instructor sees announcements targeted to instructors or all', function () {
    $author = User::factory()->asAdmin()->create();
    $instructor = User::factory()->asInstructor()->create();

    Announcement::factory()->forAuthor($author)->forAudience(AnnouncementAudience::All)->create(['title' => 'General News']);
    Announcement::factory()->forAuthor($author)->forAudience(AnnouncementAudience::Instructors)->create(['title' => 'Instructor Info']);
    Announcement::factory()->forAuthor($author)->forAudience(AnnouncementAudience::Students)->create(['title' => 'Student Only']);

    Livewire::actingAs($instructor)
        ->test('pages::dashboard')
        ->assertSee('General News')
        ->assertSee('Instructor Info')
        ->assertDontSee('Student Only');
});

test('admin sees summary stats', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);

    User::factory()->asStudent()->count(3)->create();

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id, 'is_active' => true]);
    Enrollment::factory()->forSection($section)->count(2)->create();

    Livewire::actingAs($admin)
        ->test('pages::dashboard')
        ->assertSee('Total Students')
        ->assertSee('Enrollments This Term')
        ->assertSee('Active Sections');
});

test('admin sees recent enrollments', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create(['name' => 'Jane Doe']);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'ELC-101']);
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->create();

    Livewire::actingAs($admin)
        ->test('pages::dashboard')
        ->assertSee('Jane Doe')
        ->assertSee('ELC-101');
});

test('admin sees recent certificates', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['name' => 'Welding Technology']);
    Certificate::factory()->forProgram($program)->create(['certificate_number' => 'CERT-2026-000001']);

    Livewire::actingAs($admin)
        ->test('pages::dashboard')
        ->assertSee('Welding Technology')
        ->assertSee('CERT-2026-000001');
});

test('dashboard handles no active term gracefully', function () {
    $student = User::factory()->asStudent()->create();

    Livewire::actingAs($student)
        ->test('pages::dashboard')
        ->assertSee('No active term');
});
