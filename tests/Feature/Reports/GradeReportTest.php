<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access grade report', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.grades'))
        ->assertOk();
});

test('student cannot access grade report', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.reports.grades'))
        ->assertForbidden();
});

test('displays correct average score', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $assessment = Assessment::factory()->forSection($section)->create(['max_points' => 100]);
    $enrollment = Enrollment::factory()->forSection($section)->create();

    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment)->create(['score' => 85]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.grades');

    expect($component->get('overallStats')['total'])->toBe(1);
    expect($component->get('overallStats')['average'])->toBe(85.0);
});

test('term filter changes results', function () {
    $admin = User::factory()->asAdmin()->create();
    $term1 = Term::factory()->create(['is_active' => true]);
    $term2 = Term::factory()->create(['is_active' => false]);

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section1 = Section::factory()->forCourse($course)->create(['term_id' => $term1->id]);
    $section2 = Section::factory()->forCourse($course)->create(['term_id' => $term2->id, 'section_number' => '02']);

    $assessment1 = Assessment::factory()->forSection($section1)->create(['max_points' => 100]);
    $assessment2 = Assessment::factory()->forSection($section2)->create(['max_points' => 100]);

    $enrollment1 = Enrollment::factory()->forSection($section1)->create();
    $enrollment2 = Enrollment::factory()->forSection($section2)->create();

    Grade::factory()->forEnrollment($enrollment1)->forAssessment($assessment1)->create(['score' => 90]);
    Grade::factory()->forEnrollment($enrollment2)->forAssessment($assessment2)->create(['score' => 70]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.grades');

    // Default active term → 90%
    expect($component->get('overallStats')['average'])->toBe(90.0);

    // Switch to term2 → 70%
    $component->set('termId', (string) $term2->id);
    expect($component->get('overallStats')['average'])->toBe(70.0);
});

test('course filter narrows results', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $program = Program::factory()->create();
    $courseA = Course::factory()->for($program)->create(['code' => 'GRD-101']);
    $courseB = Course::factory()->for($program)->create(['code' => 'GRD-201']);

    $sectionA = Section::factory()->forCourse($courseA)->create(['term_id' => $term->id]);
    $sectionB = Section::factory()->forCourse($courseB)->create(['term_id' => $term->id]);

    $assessmentA = Assessment::factory()->forSection($sectionA)->create(['max_points' => 100]);
    $assessmentB = Assessment::factory()->forSection($sectionB)->create(['max_points' => 100]);

    $enrollmentA = Enrollment::factory()->forSection($sectionA)->create();
    $enrollmentB = Enrollment::factory()->forSection($sectionB)->create();

    Grade::factory()->forEnrollment($enrollmentA)->forAssessment($assessmentA)->create(['score' => 95]);
    Grade::factory()->forEnrollment($enrollmentB)->forAssessment($assessmentB)->create(['score' => 60]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.grades')
        ->set('courseId', (string) $courseA->id);

    expect($component->get('overallStats')['total'])->toBe(1);
    expect($component->get('overallStats')['average'])->toBe(95.0);
});

test('grade distribution buckets correct', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $assessment = Assessment::factory()->forSection($section)->create(['max_points' => 100]);

    $enrollment1 = Enrollment::factory()->forSection($section)->create();
    $enrollment2 = Enrollment::factory()->forSection($section)->create();
    $enrollment3 = Enrollment::factory()->forSection($section)->create();

    Grade::factory()->forEnrollment($enrollment1)->forAssessment($assessment)->create(['score' => 95]); // A
    Grade::factory()->forEnrollment($enrollment2)->forAssessment($assessment)->create(['score' => 72]); // C
    Grade::factory()->forEnrollment($enrollment3)->forAssessment($assessment)->create(['score' => 45]); // F

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.grades');

    $distribution = collect($component->get('gradeDistribution'));
    expect($distribution->firstWhere('letter', 'A')->count)->toBe(1);
    expect($distribution->firstWhere('letter', 'C')->count)->toBe(1);
    expect($distribution->firstWhere('letter', 'F')->count)->toBe(1);
    expect($distribution->firstWhere('letter', 'B')->count)->toBe(0);
    expect($distribution->firstWhere('letter', 'D')->count)->toBe(0);
});

test('handles sections with no grades', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.grades')
        ->assertSee('No grades recorded');
});

test('handles max_points zero gracefully', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $assessment = Assessment::factory()->forSection($section)->create(['max_points' => 0]);
    $enrollment = Enrollment::factory()->forSection($section)->create();

    Grade::factory()->forEnrollment($enrollment)->forAssessment($assessment)->create(['score' => 0]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.grades');

    expect($component->get('overallStats')['average'])->toBe(0.0);
});
