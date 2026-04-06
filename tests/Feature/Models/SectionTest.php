<?php

use App\Models\Course;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('section has fillable attributes', function () {
    $section = Section::factory()->create([
        'section_number' => '01',
        'max_enrollment' => 25,
    ]);

    expect($section->section_number)->toBe('01');
    expect($section->max_enrollment)->toBe(25);
});

test('section belongs to course', function () {
    $course = Course::factory()->create();
    $section = Section::factory()->forCourse($course)->create();

    expect($section->course->id)->toBe($course->id);
});

test('section belongs to term', function () {
    $term = Term::factory()->create();
    $section = Section::factory()->forTerm($term)->create();

    expect($section->term->id)->toBe($term->id);
});

test('section belongs to instructor', function () {
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->withInstructor($instructor)->create();

    expect($section->instructor->id)->toBe($instructor->id);
});

test('section has many schedules', function () {
    $section = Section::factory()->create();
    SectionSchedule::factory()->forSection($section)->count(2)->create();

    expect($section->schedules)->toHaveCount(2);
});

test('section displayCode returns correct format', function () {
    $course = Course::factory()->create(['code' => 'WLD-101']);
    $section = Section::factory()->forCourse($course)->create(['section_number' => '02']);

    expect($section->displayCode())->toBe('WLD-101-02');
});

test('section casts is_active to boolean', function () {
    $section = Section::factory()->create(['is_active' => true]);

    expect($section->is_active)->toBeBool();
    expect($section->is_active)->toBeTrue();
});

test('section enforces unique constraint on course_id, term_id, section_number', function () {
    $course = Course::factory()->create();
    $term = Term::factory()->create();
    Section::factory()->forCourse($course)->forTerm($term)->create(['section_number' => '01']);

    Section::factory()->forCourse($course)->forTerm($term)->create(['section_number' => '01']);
})->throws(QueryException::class);
