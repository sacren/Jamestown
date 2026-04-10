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

function buildGradeFixture(?User $instructor = null): array
{
    $instructor ??= User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $assessment = Assessment::factory()->forSection($section)->withMaxPoints(100)->create();

    return compact('instructor', 'section', 'student', 'enrollment', 'assessment');
}

test('instructor can view grade entry page for own section', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor'])
        ->get(route('instructor.grades.entry', $f['section']))
        ->assertOk();
});

test('instructor cannot view grade entry page for another instructors section', function () {
    $f = buildGradeFixture();
    $other = User::factory()->asInstructor()->create();

    $this->actingAs($other)
        ->get(route('instructor.grades.entry', $f['section']))
        ->assertForbidden();
});

test('guest is redirected to login from grade entry page', function () {
    $f = buildGradeFixture();

    $this->get(route('instructor.grades.entry', $f['section']))
        ->assertRedirect(route('login'));
});

test('student cannot access grade entry page', function () {
    $f = buildGradeFixture();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('instructor.grades.entry', $f['section']))
        ->assertForbidden();
});

test('grade entry page shows enrolled students when assessment selected', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->assertSee($f['student']->name);
});

test('instructor can save grades for an assessment', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '87.5')
        ->call('saveGrades');

    $grade = Grade::query()
        ->where('enrollment_id', $f['enrollment']->id)
        ->where('assessment_id', $f['assessment']->id)
        ->first();

    expect($grade)->not->toBeNull();
    expect((float) $grade->score)->toBe(87.5);
});

test('instructor can update existing grades', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '70')
        ->call('saveGrades');

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '95')
        ->call('saveGrades');

    expect(Grade::query()->where('enrollment_id', $f['enrollment']->id)->count())->toBe(1);
    $grade = Grade::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect((float) $grade->score)->toBe(95.0);
});

test('grade score must be within 0 and max_points', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    $component = Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '150')
        ->call('saveGrades');

    expect($component->get('scoreErrors'))->not->toBeEmpty();
    expect(Grade::query()->count())->toBe(0);
});

test('grade score cannot be negative', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    $component = Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '-5')
        ->call('saveGrades');

    expect($component->get('scoreErrors'))->not->toBeEmpty();
    expect(Grade::query()->count())->toBe(0);
});

test('grades record the authenticated user as graded_by', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '80')
        ->call('saveGrades');

    $grade = Grade::query()->first();
    expect($grade->graded_by)->toBe($f['instructor']->id);
});

test('dropped students do not appear in grade entry form', function () {
    $f = buildGradeFixture();

    $droppedStudent = User::factory()->asStudent()->create(['name' => 'Dropped Student Name']);
    Enrollment::factory()
        ->forStudent($droppedStudent)
        ->forSection($f['section'])
        ->dropped()
        ->create();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->assertDontSee('Dropped Student Name');
});

test('instructor can add notes to grades', function () {
    $f = buildGradeFixture();

    $this->actingAs($f['instructor']);

    Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '85')
        ->set('grades.'.$f['enrollment']->id.'.notes', 'Excellent work')
        ->call('saveGrades');

    $grade = Grade::query()->first();
    expect($grade->notes)->toBe('Excellent work');
});

test('grade entry loads existing grades when assessment is selected', function () {
    $f = buildGradeFixture();
    Grade::factory()
        ->forEnrollment($f['enrollment'])
        ->forAssessment($f['assessment'])
        ->withScore(78.5)
        ->create();

    $this->actingAs($f['instructor']);

    $component = Livewire::test('pages::instructor.grades.entry', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id);

    expect($component->get('grades')[$f['enrollment']->id]['score'])->toBe('78.50');
});
