<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access program completion report', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.program-completion'))
        ->assertOk();
});

test('student cannot access program completion report', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.reports.program-completion'))
        ->assertForbidden();
});

test('shows correct student count per program', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['name' => 'Sigma Welding', 'is_active' => true]);
    $course = Course::factory()->for($program)->create();
    $term = Term::factory()->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    $student1 = User::factory()->asStudent()->create();
    $student2 = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($student1)->forSection($section)->create();
    Enrollment::factory()->forStudent($student2)->forSection($section)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion');

    $programRow = $component->get('programStats')->first(fn ($r) => $r->program->name === 'Sigma Welding');
    expect($programRow->total_students)->toBe(2);
});

test('shows correct certificate count excluding revoked', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['name' => 'Tau Electric', 'is_active' => true]);
    $course = Course::factory()->for($program)->create();
    $term = Term::factory()->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    $student1 = User::factory()->asStudent()->create();
    $student2 = User::factory()->asStudent()->create();
    $student3 = User::factory()->asStudent()->create();

    Enrollment::factory()->forStudent($student1)->forSection($section)->create();
    Enrollment::factory()->forStudent($student2)->forSection($section)->create();
    Enrollment::factory()->forStudent($student3)->forSection($section)->create();

    Certificate::factory()->forStudent($student1)->forProgram($program)->create();
    Certificate::factory()->forStudent($student2)->forProgram($program)->create();
    Certificate::factory()->forStudent($student3)->forProgram($program)->revoked()->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion');

    $programRow = $component->get('programStats')->first(fn ($r) => $r->program->name === 'Tau Electric');
    expect($programRow->certificates_issued)->toBe(2);
    expect($programRow->certificates_revoked)->toBe(1);
});

test('completion rate calculated correctly', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['name' => 'Upsilon HVAC', 'is_active' => true]);
    $course = Course::factory()->for($program)->create();
    $term = Term::factory()->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    $student1 = User::factory()->asStudent()->create();
    $student2 = User::factory()->asStudent()->create();
    Enrollment::factory()->forStudent($student1)->forSection($section)->create();
    Enrollment::factory()->forStudent($student2)->forSection($section)->create();

    Certificate::factory()->forStudent($student1)->forProgram($program)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion');

    $programRow = $component->get('programStats')->first(fn ($r) => $r->program->name === 'Upsilon HVAC');
    expect($programRow->completion_rate)->toBe(50.0);
    expect($programRow->in_progress)->toBe(1);
});

test('program filter narrows results', function () {
    $admin = User::factory()->asAdmin()->create();
    $programA = Program::factory()->create(['name' => 'Phi Plumbing', 'is_active' => true]);
    $programB = Program::factory()->create(['name' => 'Chi Auto', 'is_active' => true]);

    $courseA = Course::factory()->for($programA)->create();
    $courseB = Course::factory()->for($programB)->create();
    $term = Term::factory()->create();
    $sectionA = Section::factory()->forCourse($courseA)->create(['term_id' => $term->id]);
    $sectionB = Section::factory()->forCourse($courseB)->create(['term_id' => $term->id]);

    Enrollment::factory()->forSection($sectionA)->create();
    Enrollment::factory()->forSection($sectionB)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion')
        ->set('programId', (string) $programA->id);

    $names = $component->get('programStats')->pluck('program.name')->toArray();
    expect($names)->toContain('Phi Plumbing');
    expect($names)->not->toContain('Chi Auto');
});

test('recent certificates displayed with correct data', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create(['name' => 'Omega Graduate']);
    $program = Program::factory()->create(['name' => 'Psi Tech', 'is_active' => true]);

    Certificate::factory()->forStudent($student)->forProgram($program)->create([
        'certificate_number' => 'CERT-RPT-001',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion')
        ->assertSee('Omega Graduate')
        ->assertSee('Psi Tech')
        ->assertSee('CERT-RPT-001');
});

test('handles programs with no enrollments', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['name' => 'Empty Program', 'is_active' => true]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion');

    $row = $component->get('programStats')->first(fn ($r) => $r->program->name === 'Empty Program');
    expect($row->total_students)->toBe(0);
    expect($row->completion_rate)->toEqual(0);
});

test('handles no certificates gracefully', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['is_active' => true]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.program-completion');

    expect($component->get('overallStats')['total_certificates'])->toBe(0);
});
