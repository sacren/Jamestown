<?php

use App\Enums\EnrollmentStatus;
use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Certificate;
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

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('EnrollmentStatusChanged via returns database', function () {
    $enrollment = Enrollment::factory()->create();
    $notification = new EnrollmentStatusChanged($enrollment, EnrollmentStatus::Completed);

    expect($notification->via($enrollment->student))->toBe(['database']);
});

test('EnrollmentStatusChanged toArray contains required keys', function () {
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'WLD-101']);
    $section = Section::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->forSection($section)->create();

    $notification = new EnrollmentStatusChanged($enrollment, EnrollmentStatus::Completed);
    $data = $notification->toArray($enrollment->student);

    expect($data)->toHaveKeys(['title', 'message', 'url', 'icon']);
    expect($data['message'])->toContain('WLD-101');
    expect($data['message'])->toContain('Completed');
});

test('GradePosted via returns database', function () {
    $enrollment = Enrollment::factory()->create();
    $assessment = Assessment::factory()->for($enrollment->section)->create();
    $notification = new GradePosted($enrollment, $assessment);

    expect($notification->via($enrollment->student))->toBe(['database']);
});

test('GradePosted toArray contains assessment title and course code', function () {
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['code' => 'HVAC-201']);
    $section = Section::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->forSection($section)->create();
    $assessment = Assessment::factory()->for($section)->create(['title' => 'Midterm Exam']);

    $notification = new GradePosted($enrollment, $assessment);
    $data = $notification->toArray($enrollment->student);

    expect($data)->toHaveKeys(['title', 'message', 'url', 'icon']);
    expect($data['message'])->toContain('Midterm Exam');
    expect($data['message'])->toContain('HVAC-201');
});

test('CertificateIssued via returns database', function () {
    $certificate = Certificate::factory()->create();
    $notification = new CertificateIssued($certificate);

    expect($notification->via($certificate->student))->toBe(['database']);
});

test('CertificateIssued toArray contains program name and certificate number', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);
    $certificate = Certificate::factory()->forProgram($program)->create(['certificate_number' => 'CERT-2026-000099']);

    $notification = new CertificateIssued($certificate);
    $data = $notification->toArray($certificate->student);

    expect($data)->toHaveKeys(['title', 'message', 'url', 'icon']);
    expect($data['message'])->toContain('Welding Technology');
    expect($data['message'])->toContain('CERT-2026-000099');
});

test('NewAnnouncement via returns database', function () {
    $announcement = Announcement::factory()->create();
    $notification = new NewAnnouncement($announcement);

    expect($notification->via(User::factory()->create()))->toBe(['database']);
});

test('NewAnnouncement toArray contains announcement title and truncated body', function () {
    $announcement = Announcement::factory()->create([
        'title' => 'Welcome to Fall 2026',
        'body' => 'This is the body of the announcement with important details.',
    ]);

    $notification = new NewAnnouncement($announcement);
    $data = $notification->toArray(User::factory()->create());

    expect($data)->toHaveKeys(['title', 'message', 'url', 'icon']);
    expect($data['title'])->toBe('Welcome to Fall 2026');
    expect($data['message'])->toContain('This is the body');
});
