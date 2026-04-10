<?php

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('student can view their grades page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.grades'))
        ->assertOk();
});

test('student only sees their own grades', function () {
    $student = User::factory()->asStudent()->create();
    $otherStudent = User::factory()->asStudent()->create();

    $section = Section::factory()->create();
    $assessment = Assessment::factory()->forSection($section)->create(['title' => 'Visible Assessment']);

    $myEnrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $otherEnrollment = Enrollment::factory()->forStudent($otherStudent)->forSection($section)->create();

    Grade::factory()->forEnrollment($myEnrollment)->forAssessment($assessment)->withScore(80)->create();
    Grade::factory()->forEnrollment($otherEnrollment)->forAssessment($assessment)->withScore(50)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.grades');
    $grades = $component->instance()->grades;

    expect($grades)->toHaveCount(1);
    expect($grades->first()->enrollment_id)->toBe($myEnrollment->id);
});

test('guest is redirected to login from student grades', function () {
    $this->get(route('registration.grades'))
        ->assertRedirect(route('login'));
});

test('admin cannot access student grades page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('registration.grades'))
        ->assertForbidden();
});

test('student can filter grades by term', function () {
    $student = User::factory()->asStudent()->create();

    $term1 = Term::factory()->create();
    $term2 = Term::factory()->create();
    $section1 = Section::factory()->forTerm($term1)->create();
    $section2 = Section::factory()->forTerm($term2)->create();

    $assessment1 = Assessment::factory()->forSection($section1)->create();
    $assessment2 = Assessment::factory()->forSection($section2)->create();

    $e1 = Enrollment::factory()->forStudent($student)->forSection($section1)->create();
    $e2 = Enrollment::factory()->forStudent($student)->forSection($section2)->create();

    Grade::factory()->forEnrollment($e1)->forAssessment($assessment1)->create();
    Grade::factory()->forEnrollment($e2)->forAssessment($assessment2)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.grades')
        ->set('termFilter', (string) $term1->id);

    expect($component->instance()->grades)->toHaveCount(1);
});

test('student can filter grades by section', function () {
    $student = User::factory()->asStudent()->create();

    $section1 = Section::factory()->create();
    $section2 = Section::factory()->create();

    $assessment1 = Assessment::factory()->forSection($section1)->create();
    $assessment2 = Assessment::factory()->forSection($section2)->create();

    $e1 = Enrollment::factory()->forStudent($student)->forSection($section1)->create();
    $e2 = Enrollment::factory()->forStudent($student)->forSection($section2)->create();

    Grade::factory()->forEnrollment($e1)->forAssessment($assessment1)->create();
    Grade::factory()->forEnrollment($e2)->forAssessment($assessment2)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.grades')
        ->set('sectionFilter', (string) $section1->id);

    expect($component->instance()->grades)->toHaveCount(1);
});

test('grades page shows summary statistics per section', function () {
    $student = User::factory()->asStudent()->create();
    $section = Section::factory()->create();
    $assessment1 = Assessment::factory()->forSection($section)->withMaxPoints(100)->create();
    $assessment2 = Assessment::factory()->forSection($section)->withMaxPoints(50)->create();

    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment1)->withScore(80)->create();
    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment2)->withScore(40)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.grades');
    $summary = $component->instance()->summary;

    expect($summary)->toHaveCount(1);
    expect($summary->first()->percentage)->toBe(80.0);
});

test('student with no enrollments sees empty state', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student);

    Livewire::test('pages::registration.grades')
        ->assertSee(__('No enrollments'));
});

test('grades page calculates percentage correctly', function () {
    $student = User::factory()->asStudent()->create();
    $section = Section::factory()->create();
    $assessment = Assessment::factory()->forSection($section)->withMaxPoints(200)->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment)->withScore(150)->create();

    $this->actingAs($student);

    $component = Livewire::test('pages::registration.grades');
    $summary = $component->instance()->summary;

    expect($summary->first()->percentage)->toBe(75.0);
});
