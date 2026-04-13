<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access issue page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.issue'))
        ->assertOk();
});

test('registrar can access issue page', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.certificates.issue'))
        ->assertOk();
});

test('instructor is forbidden from issue page', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.certificates.issue'))
        ->assertForbidden();
});

test('student is forbidden from issue page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.certificates.issue'))
        ->assertForbidden();
});

test('selecting student and program shows completion progress', function () {
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
        ->assertSee('Completed')
        ->assertSee($course->code);
});

test('can issue certificate for eligible student', function () {
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
        ->call('issueCertificate')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Certificate::where('user_id', $student->id)->where('program_id', $program->id)->exists())->toBeTrue();
});

test('cannot issue certificate for ineligible student', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    Course::factory()->for($program)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.issue')
        ->set('student_id', (string) $student->id)
        ->set('program_id', (string) $program->id)
        ->call('issueCertificate')
        ->assertHasErrors('program_id');

    expect(Certificate::where('user_id', $student->id)->exists())->toBeFalse();
});

test('cannot issue duplicate certificate for same student and program', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    Certificate::factory()->forStudent($student)->forProgram($program)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.issue')
        ->set('student_id', (string) $student->id)
        ->set('program_id', (string) $program->id)
        ->call('issueCertificate')
        ->assertHasErrors('program_id');
});

test('issued certificate has correct issued_by', function () {
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

    $certificate = Certificate::where('user_id', $student->id)->first();
    expect($certificate->issued_by)->toBe($admin->id);
});

test('certificate number is auto-generated', function () {
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

    $certificate = Certificate::where('user_id', $student->id)->first();
    expect($certificate->certificate_number)->toMatch('/^CERT-\d{4}-\d{6}$/');
});
