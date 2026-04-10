<?php

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Section;
use Carbon\CarbonInterface;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('assessment has fillable attributes', function () {
    $section = Section::factory()->create();

    $assessment = Assessment::create([
        'section_id' => $section->id,
        'title' => 'Midterm',
        'type' => AssessmentType::Exam,
        'description' => 'Covers chapters 1-5',
        'max_points' => 100,
        'due_date' => '2026-05-15',
        'sort_order' => 2,
    ]);

    expect($assessment->section_id)->toBe($section->id);
    expect($assessment->title)->toBe('Midterm');
    expect($assessment->description)->toBe('Covers chapters 1-5');
    expect($assessment->sort_order)->toBe(2);
});

test('assessment belongs to a section', function () {
    $section = Section::factory()->create();
    $assessment = Assessment::factory()->forSection($section)->create();

    expect($assessment->section)->toBeInstanceOf(Section::class);
    expect($assessment->section->id)->toBe($section->id);
});

test('assessment has many grades', function () {
    $assessment = Assessment::factory()->create();
    Grade::factory()->count(3)->forAssessment($assessment)->create();

    expect($assessment->grades)->toHaveCount(3);
});

test('assessment casts type to AssessmentType enum', function () {
    $assessment = Assessment::factory()->exam()->create();

    expect($assessment->type)->toBeInstanceOf(AssessmentType::class);
    expect($assessment->type)->toBe(AssessmentType::Exam);
});

test('assessment casts due_date to Carbon date', function () {
    $assessment = Assessment::factory()->withDueDate('2026-05-15')->create();

    expect($assessment->due_date)->toBeInstanceOf(CarbonInterface::class);
    expect($assessment->due_date->format('Y-m-d'))->toBe('2026-05-15');
});

test('assessment casts max_points to integer', function () {
    $assessment = Assessment::factory()->withMaxPoints(75)->create();

    expect($assessment->max_points)->toBe(75);
});

test('section has many assessments relationship', function () {
    $section = Section::factory()->create();
    Assessment::factory()->count(4)->forSection($section)->create();

    expect($section->assessments)->toHaveCount(4);
});

test('assessment factory states work correctly', function () {
    expect(Assessment::factory()->exam()->create()->type)->toBe(AssessmentType::Exam);
    expect(Assessment::factory()->quiz()->create()->type)->toBe(AssessmentType::Quiz);
    expect(Assessment::factory()->project()->create()->max_points)->toBe(50);
    expect(Assessment::factory()->lab()->create()->max_points)->toBe(30);
    expect(Assessment::factory()->final()->create()->max_points)->toBe(200);
});
