<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('student can view transcript page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.transcript'))
        ->assertOk();
});

test('transcript shows enrollments grouped by program', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    Livewire::actingAs($student)
        ->test('pages::registration.transcript')
        ->assertSee($program->name)
        ->assertSee($course->code);
});

test('transcript shows course code name term status grade and credits', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create(['credit_hours' => 3]);
    $section = Section::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->completed()->create();

    $assessment = Assessment::factory()->for($section)->create(['max_points' => 100]);
    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'assessment_id' => $assessment->id,
        'score' => 85,
    ]);

    Livewire::actingAs($student)
        ->test('pages::registration.transcript')
        ->assertSee($course->code)
        ->assertSee($course->name)
        ->assertSee('Completed')
        ->assertSee('85%');
});

test('per-program summary shows correct course count and credits', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create();
    $course1 = Course::factory()->for($program)->create(['credit_hours' => 3]);
    $course2 = Course::factory()->for($program)->create(['credit_hours' => 4]);

    $section1 = Section::factory()->forCourse($course1)->create();
    Enrollment::factory()->forStudent($student)->forSection($section1)->completed()->create();

    $section2 = Section::factory()->forCourse($course2)->create();
    Enrollment::factory()->forStudent($student)->forSection($section2)->create(); // enrolled, not completed

    $data = Livewire::actingAs($student)
        ->test('pages::registration.transcript')
        ->get('programData');

    expect($data->first()->completed_count)->toBe(1);
    expect($data->first()->credits_earned)->toBe(3);
});

test('transcript only shows own data', function () {
    $student = User::factory()->asStudent()->create();
    $other = User::factory()->asStudent()->create();

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($other)->forSection($section)->completed()->create();

    Livewire::actingAs($student)
        ->test('pages::registration.transcript')
        ->assertDontSee($course->code);
});

test('admin is forbidden from student transcript', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('registration.transcript'))
        ->assertForbidden();
});

test('instructor is forbidden from student transcript', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('registration.transcript'))
        ->assertForbidden();
});
