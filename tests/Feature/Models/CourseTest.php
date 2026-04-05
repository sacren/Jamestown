<?php

use App\Models\Course;
use App\Models\Program;

test('course belongs to a program', function () {
    $program = Program::factory()->create();
    $course = Course::factory()->forProgram($program)->create();

    expect($course->program->id)->toBe($program->id);
});

test('course has fillable attributes', function () {
    $course = Course::factory()->create([
        'name' => 'Introduction to Welding',
        'code' => 'WLD-101',
    ]);

    expect($course->name)->toBe('Introduction to Welding');
    expect($course->code)->toBe('WLD-101');
});

test('course casts is_active to boolean', function () {
    $course = Course::factory()->create(['is_active' => true]);

    expect($course->is_active)->toBeTrue();
    expect($course->is_active)->toBeBool();
});

test('course has prerequisites relationship', function () {
    $program = Program::factory()->create();
    $intro = Course::factory()->forProgram($program)->create();
    $advanced = Course::factory()->forProgram($program)->create();

    $advanced->prerequisites()->attach($intro->id);

    expect($advanced->prerequisites)->toHaveCount(1);
    expect($advanced->prerequisites->first()->id)->toBe($intro->id);
});

test('course has prerequisiteFor relationship', function () {
    $program = Program::factory()->create();
    $intro = Course::factory()->forProgram($program)->create();
    $advanced = Course::factory()->forProgram($program)->create();

    $advanced->prerequisites()->attach($intro->id);

    expect($intro->prerequisiteFor)->toHaveCount(1);
    expect($intro->prerequisiteFor->first()->id)->toBe($advanced->id);
});

test('course totalContactHours sums lecture and lab hours', function () {
    $course = Course::factory()->create([
        'lecture_hours' => 2,
        'lab_hours' => 4,
    ]);

    expect($course->totalContactHours())->toBe(6);
});

test('deleting course cascades prerequisites', function () {
    $program = Program::factory()->create();
    $intro = Course::factory()->forProgram($program)->create();
    $advanced = Course::factory()->forProgram($program)->create();

    $advanced->prerequisites()->attach($intro->id);

    $advanced->delete();

    expect(DB::table('course_prerequisites')->count())->toBe(0);
});
