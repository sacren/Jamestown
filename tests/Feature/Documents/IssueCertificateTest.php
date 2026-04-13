<?php

use App\Actions\Documents\IssueCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->action = app(IssueCertificate::class);
    $this->issuer = User::factory()->create();
});

test('issues certificate for eligible student', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    $certificate = $this->action->handle($student, $program, $this->issuer);

    expect($certificate)->toBeInstanceOf(Certificate::class);
    expect($certificate->user_id)->toBe($student->id);
    expect($certificate->program_id)->toBe($program->id);
    expect($certificate->issued_by)->toBe($this->issuer->id);
});

test('refuses to issue for ineligible student', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();
    Course::factory()->for($program)->create();

    $this->action->handle($student, $program, $this->issuer);
})->throws(InvalidArgumentException::class, 'Student has not completed all courses in this program.');

test('refuses to issue if certificate already exists for same student and program', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    Certificate::factory()->forStudent($student)->forProgram($program)->create();

    $this->action->handle($student, $program, $this->issuer);
})->throws(InvalidArgumentException::class, 'A certificate already exists for this student and program.');

test('refuses to issue if revoked certificate exists for same student and program', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    Certificate::factory()->forStudent($student)->forProgram($program)->revoked()->create();

    $this->action->handle($student, $program, $this->issuer);
})->throws(InvalidArgumentException::class, 'A certificate already exists for this student and program.');

test('certificate number is auto-generated in correct format', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    $certificate = $this->action->handle($student, $program, $this->issuer);

    expect($certificate->certificate_number)->toMatch('/^CERT-\d{4}-\d{6}$/');
});

test('issued_by is set to the issuer', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    $certificate = $this->action->handle($student, $program, $this->issuer);

    expect($certificate->issued_by)->toBe($this->issuer->id);
});
