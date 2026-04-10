<?php

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('grade has fillable attributes', function () {
    $enrollment = Enrollment::factory()->create();
    $assessment = Assessment::factory()->create();
    $grader = User::factory()->asInstructor()->create();

    $grade = Grade::create([
        'enrollment_id' => $enrollment->id,
        'assessment_id' => $assessment->id,
        'score' => 87.5,
        'letter_grade' => 'B+',
        'notes' => 'Good work',
        'graded_by' => $grader->id,
    ]);

    expect($grade->enrollment_id)->toBe($enrollment->id);
    expect($grade->assessment_id)->toBe($assessment->id);
    expect($grade->letter_grade)->toBe('B+');
    expect($grade->notes)->toBe('Good work');
});

test('grade belongs to an enrollment', function () {
    $enrollment = Enrollment::factory()->create();
    $grade = Grade::factory()->forEnrollment($enrollment)->create();

    expect($grade->enrollment)->toBeInstanceOf(Enrollment::class);
    expect($grade->enrollment->id)->toBe($enrollment->id);
});

test('grade belongs to an assessment', function () {
    $assessment = Assessment::factory()->create();
    $grade = Grade::factory()->forAssessment($assessment)->create();

    expect($grade->assessment)->toBeInstanceOf(Assessment::class);
    expect($grade->assessment->id)->toBe($assessment->id);
});

test('grade belongs to a grader user', function () {
    $grader = User::factory()->asInstructor()->create();
    $grade = Grade::factory()->gradedBy($grader)->create();

    expect($grade->grader)->toBeInstanceOf(User::class);
    expect($grade->grader->id)->toBe($grader->id);
});

test('grade casts score to decimal', function () {
    $grade = Grade::factory()->withScore(87.5)->create();

    expect((float) $grade->score)->toBe(87.5);
});

test('grade enforces unique constraint on enrollment and assessment', function () {
    $enrollment = Enrollment::factory()->create();
    $assessment = Assessment::factory()->create();

    Grade::factory()
        ->forEnrollment($enrollment)
        ->forAssessment($assessment)
        ->create();

    expect(fn () => Grade::factory()
        ->forEnrollment($enrollment)
        ->forAssessment($assessment)
        ->create()
    )->toThrow(QueryException::class);
});

test('enrollment has many grades relationship', function () {
    $enrollment = Enrollment::factory()->create();

    Grade::factory()
        ->count(3)
        ->forEnrollment($enrollment)
        ->sequence(
            ['assessment_id' => Assessment::factory()],
            ['assessment_id' => Assessment::factory()],
            ['assessment_id' => Assessment::factory()],
        )
        ->create();

    expect($enrollment->grades)->toHaveCount(3);
});

test('grade factory states work correctly', function () {
    expect((float) Grade::factory()->perfect()->create()->score)->toBe(100.0);
    expect((float) Grade::factory()->zero()->create()->score)->toBe(0.0);
    expect(Grade::factory()->withLetterGrade('A-')->create()->letter_grade)->toBe('A-');
});
