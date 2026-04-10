<?php

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function buildAssessmentFixture(?User $instructor = null): array
{
    $instructor ??= User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();

    return compact('instructor', 'section');
}

test('instructor can view assessment index for own section', function () {
    $f = buildAssessmentFixture();

    $this->actingAs($f['instructor'])
        ->get(route('instructor.assessments.index', $f['section']))
        ->assertOk();
});

test('instructor cannot view assessment index for another instructors section', function () {
    $f = buildAssessmentFixture();
    $other = User::factory()->asInstructor()->create();

    $this->actingAs($other)
        ->get(route('instructor.assessments.index', $f['section']))
        ->assertForbidden();
});

test('guest is redirected to login from assessment index page', function () {
    $f = buildAssessmentFixture();

    $this->get(route('instructor.assessments.index', $f['section']))
        ->assertRedirect(route('login'));
});

test('student cannot access instructor assessment index page', function () {
    $f = buildAssessmentFixture();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('instructor.assessments.index', $f['section']))
        ->assertForbidden();
});

test('assessment index shows section assessments', function () {
    $f = buildAssessmentFixture();
    Assessment::factory()->forSection($f['section'])->create(['title' => 'Midterm Exam']);

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.index', ['section' => $f['section']])
        ->assertSee('Midterm Exam');
});

test('instructor can create an assessment', function () {
    $f = buildAssessmentFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.create', ['section' => $f['section']])
        ->set('title', 'Quiz 1')
        ->set('type', AssessmentType::Quiz->value)
        ->set('max_points', 25)
        ->call('createAssessment');

    expect(Assessment::query()->where('section_id', $f['section']->id)->where('title', 'Quiz 1')->exists())->toBeTrue();
});

test('assessment creation validates required fields', function () {
    $f = buildAssessmentFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.create', ['section' => $f['section']])
        ->set('title', '')
        ->call('createAssessment')
        ->assertHasErrors(['title']);
});

test('instructor can view edit assessment page', function () {
    $f = buildAssessmentFixture();
    $assessment = Assessment::factory()->forSection($f['section'])->create();

    $this->actingAs($f['instructor'])
        ->get(route('instructor.assessments.edit', ['section' => $f['section'], 'assessment' => $assessment]))
        ->assertOk();
});

test('instructor can update an assessment', function () {
    $f = buildAssessmentFixture();
    $assessment = Assessment::factory()->forSection($f['section'])->create();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.edit', ['section' => $f['section'], 'assessment' => $assessment])
        ->set('title', 'Updated Title')
        ->set('max_points', 150)
        ->call('updateAssessment');

    expect($assessment->fresh()->title)->toBe('Updated Title');
    expect($assessment->fresh()->max_points)->toBe(150);
});

test('instructor cannot edit assessment for another instructors section', function () {
    $f = buildAssessmentFixture();
    $assessment = Assessment::factory()->forSection($f['section'])->create();
    $other = User::factory()->asInstructor()->create();

    $this->actingAs($other)
        ->get(route('instructor.assessments.edit', ['section' => $f['section'], 'assessment' => $assessment]))
        ->assertForbidden();
});

test('instructor can delete an assessment without grades', function () {
    $f = buildAssessmentFixture();
    $assessment = Assessment::factory()->forSection($f['section'])->create();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.index', ['section' => $f['section']])
        ->call('deleteAssessment', $assessment->id);

    expect(Assessment::query()->find($assessment->id))->toBeNull();
});

test('instructor cannot delete an assessment that has grades', function () {
    $f = buildAssessmentFixture();
    $assessment = Assessment::factory()->forSection($f['section'])->create();
    Grade::factory()->forAssessment($assessment)->create();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.index', ['section' => $f['section']])
        ->call('deleteAssessment', $assessment->id);

    expect(Assessment::query()->find($assessment->id))->not->toBeNull();
});

test('assessment edit validates assessment belongs to section', function () {
    $f = buildAssessmentFixture();
    $otherSection = Section::factory()->withInstructor($f['instructor'])->create();
    $assessment = Assessment::factory()->forSection($otherSection)->create();

    $this->actingAs($f['instructor'])
        ->get(route('instructor.assessments.edit', ['section' => $f['section'], 'assessment' => $assessment]))
        ->assertNotFound();
});

test('assessment creation defaults sort_order to 0', function () {
    $f = buildAssessmentFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.assessments.create', ['section' => $f['section']])
        ->set('title', 'Homework')
        ->set('type', AssessmentType::Assignment->value)
        ->set('max_points', 10)
        ->call('createAssessment');

    $assessment = Assessment::query()->where('title', 'Homework')->first();
    expect($assessment->sort_order)->toBe(0);
});
