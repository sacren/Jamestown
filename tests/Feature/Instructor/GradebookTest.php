<?php

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('instructor can view gradebook for own section', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();

    $this->actingAs($instructor)
        ->get(route('instructor.grades.gradebook', $section))
        ->assertOk();
});

test('instructor cannot view gradebook for another instructors section', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $other = User::factory()->asInstructor()->create();

    $this->actingAs($other)
        ->get(route('instructor.grades.gradebook', $section))
        ->assertForbidden();
});

test('guest is redirected to login from gradebook', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();

    $this->get(route('instructor.grades.gradebook', $section))
        ->assertRedirect(route('login'));
});

test('student cannot access gradebook', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('instructor.grades.gradebook', $section))
        ->assertForbidden();
});

test('gradebook shows all assessments as columns', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    Assessment::factory()->forSection($section)->create(['title' => 'Midterm', 'sort_order' => 1]);
    Assessment::factory()->forSection($section)->create(['title' => 'Final', 'sort_order' => 2]);
    $student = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->create();

    $this->actingAs($instructor);

    Livewire::test('pages::instructor.grades.gradebook', ['section' => $section])
        ->assertSee('Midterm')
        ->assertSee('Final');
});

test('gradebook calculates percentage correctly', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $assessment1 = Assessment::factory()->forSection($section)->withMaxPoints(100)->create();
    $assessment2 = Assessment::factory()->forSection($section)->withMaxPoints(50)->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();

    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment1)->withScore(80)->create();
    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment2)->withScore(40)->create();

    $this->actingAs($instructor);

    $component = Livewire::test('pages::instructor.grades.gradebook', ['section' => $section]);
    $gradebook = $component->instance()->gradebook;

    expect($gradebook->first()->percentage)->toBe(80.0);
});

test('gradebook shows zero percentage when no grades exist', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    Assessment::factory()->forSection($section)->create();
    $student = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($student)->forSection($section)->create();

    $this->actingAs($instructor);

    $component = Livewire::test('pages::instructor.grades.gradebook', ['section' => $section]);
    $gradebook = $component->instance()->gradebook;

    expect($gradebook->first()->percentage)->toBe(0.0);
});

test('gradebook shows enrolled students only', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    Assessment::factory()->forSection($section)->create();

    $enrolledStudent = User::factory()->asStudent()->create(['name' => 'Enrolled Student']);
    Enrollment::factory()->forStudent($enrolledStudent)->forSection($section)->create();

    $droppedStudent = User::factory()->asStudent()->create(['name' => 'Dropped Student Zxy']);
    Enrollment::factory()->forStudent($droppedStudent)->forSection($section)->dropped()->create();

    $this->actingAs($instructor);

    Livewire::test('pages::instructor.grades.gradebook', ['section' => $section])
        ->assertSee('Enrolled Student')
        ->assertDontSee('Dropped Student Zxy');
});
