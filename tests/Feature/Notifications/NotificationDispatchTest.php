<?php

use App\Enums\EnrollmentStatus;
use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use App\Notifications\CertificateIssued;
use App\Notifications\EnrollmentStatusChanged;
use App\Notifications\GradePosted;
use App\Notifications\NewAnnouncement;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('completing enrollment dispatches notification to student', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->create(['status' => EnrollmentStatus::Enrolled]);

    Livewire::actingAs($admin)
        ->test('pages::admin.enrollments.index')
        ->call('completeEnrollment', $enrollment->id);

    Notification::assertSentTo($student, EnrollmentStatusChanged::class);
});

test('dropping enrollment dispatches notification to student', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->create(['status' => EnrollmentStatus::Enrolled]);

    Livewire::actingAs($admin)
        ->test('pages::admin.enrollments.index')
        ->call('dropEnrollment', $enrollment->id);

    Notification::assertSentTo($student, EnrollmentStatusChanged::class);
});

test('withdrawing enrollment dispatches notification to student', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->create(['status' => EnrollmentStatus::Enrolled]);

    Livewire::actingAs($admin)
        ->test('pages::admin.enrollments.index')
        ->call('withdrawEnrollment', $enrollment->id);

    Notification::assertSentTo($student, EnrollmentStatusChanged::class);
});

test('saving grades dispatches notification to each student', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $assessment = Assessment::factory()->for($section)->create(['max_points' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.grades.manage', ['section' => $section])
        ->set('assessmentId', (string) $assessment->id)
        ->set("grades.{$enrollment->id}.score", '85')
        ->call('saveGrades');

    Notification::assertSentTo($student, GradePosted::class);
});

test('saving grades does not dispatch when all scores are blank', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $assessment = Assessment::factory()->for($section)->create(['max_points' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.grades.manage', ['section' => $section])
        ->set('assessmentId', (string) $assessment->id)
        ->set("grades.{$enrollment->id}.score", '')
        ->call('saveGrades');

    Notification::assertNotSentTo($student, GradePosted::class);
});

test('saving grades dispatches only once per student', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    $enrollment1 = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $assessment = Assessment::factory()->for($section)->create(['max_points' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.grades.manage', ['section' => $section])
        ->set('assessmentId', (string) $assessment->id)
        ->set("grades.{$enrollment1->id}.score", '90')
        ->call('saveGrades');

    Notification::assertSentToTimes($student, GradePosted::class, 1);
});

test('issuing certificate dispatches notification to student', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.issue')
        ->set('student_id', (string) $student->id)
        ->set('program_id', (string) $program->id)
        ->call('issueCertificate');

    Notification::assertSentTo($student, CertificateIssued::class);
});

test('publishing announcement dispatches to students when audience is students', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $instructor = User::factory()->asInstructor()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'Student News')
        ->set('body', 'Important info for students')
        ->set('audience', 'students')
        ->call('save', true);

    Notification::assertSentTo($student, NewAnnouncement::class);
    Notification::assertNotSentTo($instructor, NewAnnouncement::class);
    Notification::assertNotSentTo($admin, NewAnnouncement::class);
});

test('publishing announcement dispatches to students and instructors when audience is all', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $instructor = User::factory()->asInstructor()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'General News')
        ->set('body', 'Important info for everyone')
        ->set('audience', 'all')
        ->call('save', true);

    Notification::assertSentTo($student, NewAnnouncement::class);
    Notification::assertSentTo($instructor, NewAnnouncement::class);
    Notification::assertNotSentTo($admin, NewAnnouncement::class);
});

test('saving announcement as draft does not dispatch notifications', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    User::factory()->asStudent()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'Draft News')
        ->set('body', 'Not ready yet')
        ->set('audience', 'all')
        ->call('save', false);

    Notification::assertNothingSent();
});

test('re-publishing previously published announcement does not dispatch duplicate notifications', function () {
    Notification::fake();

    $admin = User::factory()->asAdmin()->create();
    User::factory()->asStudent()->create();

    $announcement = Announcement::factory()->forAuthor($admin)->create([
        'published_at' => now()->subDay(),
        'notified_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement])
        ->call('unpublish');

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement->fresh()])
        ->call('publish');

    Notification::assertNothingSent();
});
