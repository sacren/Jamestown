<?php

use App\Models\Assessment;
use App\Models\Course;
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

function buildAdminGradeFixture(): array
{
    $section = Section::factory()->create();
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    $assessment = Assessment::factory()->forSection($section)->withMaxPoints(100)->create();

    return compact('section', 'student', 'enrollment', 'assessment');
}

test('admin can view grade index page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.grades.index'))
        ->assertOk();
});

test('super admin can access grade index page', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.grades.index'))
        ->assertOk();
});

test('registrar cannot access grade management', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.grades.index'))
        ->assertForbidden();
});

test('student cannot access grade management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.grades.index'))
        ->assertForbidden();
});

test('instructor can access admin grade pages via manage-grades permission', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.grades.index'))
        ->assertOk();
});

test('guest is redirected to login from admin grades', function () {
    $this->get(route('admin.grades.index'))
        ->assertRedirect(route('login'));
});

test('admin can search sections by course name on grade index', function () {
    $admin = User::factory()->asAdmin()->create();
    $course = Course::factory()->create(['name' => 'Welding Basics', 'code' => 'WLD101']);
    Section::factory()->create(['course_id' => $course->id]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.grades.index')
        ->set('search', 'Welding')
        ->assertSee('Welding Basics');
});

test('admin can filter sections by term on grade index', function () {
    $admin = User::factory()->asAdmin()->create();
    $term1 = Term::factory()->create();
    $term2 = Term::factory()->create();
    $course1 = Course::factory()->create(['name' => 'Unique Alpha Course']);
    $course2 = Course::factory()->create(['name' => 'Unique Beta Course']);
    Section::factory()->forTerm($term1)->create(['course_id' => $course1->id]);
    Section::factory()->forTerm($term2)->create(['course_id' => $course2->id]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.grades.index')
        ->set('termFilter', (string) $term1->id)
        ->assertSee('Unique Alpha Course')
        ->assertDontSee('Unique Beta Course');
});

test('admin can view grade manage page for any section', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminGradeFixture();

    $this->actingAs($admin)
        ->get(route('admin.grades.manage', $f['section']))
        ->assertOk();
});

test('admin can save grades for any section', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminGradeFixture();

    $this->actingAs($admin);

    Livewire::test('pages::admin.grades.manage', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '92')
        ->call('saveGrades');

    $grade = Grade::query()->where('enrollment_id', $f['enrollment']->id)->first();
    expect($grade)->not->toBeNull();
    expect((float) $grade->score)->toBe(92.0);
    expect($grade->graded_by)->toBe($admin->id);
});

test('admin can update existing grades', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminGradeFixture();
    Grade::factory()
        ->forEnrollment($f['enrollment'])
        ->forAssessment($f['assessment'])
        ->withScore(60)
        ->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.grades.manage', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '85')
        ->call('saveGrades');

    expect(Grade::query()->count())->toBe(1);
    expect((float) Grade::query()->first()->score)->toBe(85.0);
});

test('admin grade entry validates score within range', function () {
    $admin = User::factory()->asAdmin()->create();
    $f = buildAdminGradeFixture();

    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.grades.manage', ['section' => $f['section']])
        ->set('assessmentId', (string) $f['assessment']->id)
        ->set('grades.'.$f['enrollment']->id.'.score', '200')
        ->call('saveGrades');

    expect($component->get('scoreErrors'))->not->toBeEmpty();
    expect(Grade::query()->count())->toBe(0);
});
