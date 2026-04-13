<?php

use App\Actions\Documents\CheckProgramCompletion;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->checker = app(CheckProgramCompletion::class);
    $this->student = User::factory()->create();
    $this->program = Program::factory()->create();
});

test('student with zero enrollments is not eligible', function () {
    $courses = Course::factory()->count(3)->for($this->program)->create();

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeFalse();
    expect($result['total_courses'])->toBe(3);
    expect($result['completed_courses'])->toHaveCount(0);
    expect($result['remaining_courses'])->toHaveCount(3);
});

test('student with some completed courses is not eligible', function () {
    $courses = Course::factory()->count(3)->for($this->program)->create();

    $section = Section::factory()->forCourse($courses[0])->create();
    Enrollment::factory()->forStudent($this->student)->forSection($section)->completed()->create();

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeFalse();
    expect($result['completed_courses'])->toHaveCount(1);
    expect($result['remaining_courses'])->toHaveCount(2);
});

test('student with all courses completed is eligible', function () {
    $courses = Course::factory()->count(3)->for($this->program)->create();

    foreach ($courses as $course) {
        $section = Section::factory()->forCourse($course)->create();
        Enrollment::factory()->forStudent($this->student)->forSection($section)->completed()->create();
    }

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeTrue();
    expect($result['completed_courses'])->toHaveCount(3);
    expect($result['remaining_courses'])->toHaveCount(0);
    expect($result['in_progress_courses'])->toHaveCount(0);
});

test('in-progress courses shown separately from remaining', function () {
    $courses = Course::factory()->count(3)->for($this->program)->create();

    $section1 = Section::factory()->forCourse($courses[0])->create();
    Enrollment::factory()->forStudent($this->student)->forSection($section1)->completed()->create();

    $section2 = Section::factory()->forCourse($courses[1])->create();
    Enrollment::factory()->forStudent($this->student)->forSection($section2)->create(); // Enrolled status

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeFalse();
    expect($result['completed_courses'])->toHaveCount(1);
    expect($result['in_progress_courses'])->toHaveCount(1);
    expect($result['remaining_courses'])->toHaveCount(1);
});

test('only counts courses in the target program', function () {
    $course = Course::factory()->for($this->program)->create();
    $otherProgram = Program::factory()->create();
    $otherCourse = Course::factory()->for($otherProgram)->create();

    $section = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($this->student)->forSection($section)->completed()->create();

    $otherSection = Section::factory()->forCourse($otherCourse)->create();
    Enrollment::factory()->forStudent($this->student)->forSection($otherSection)->completed()->create();

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeTrue();
    expect($result['total_courses'])->toBe(1);
    expect($result['completed_courses'])->toHaveCount(1);
});

test('only Completed status counts as completed', function () {
    $courses = Course::factory()->count(3)->for($this->program)->create();

    $s1 = Section::factory()->forCourse($courses[0])->create();
    Enrollment::factory()->forStudent($this->student)->forSection($s1)->completed()->create();

    $s2 = Section::factory()->forCourse($courses[1])->create();
    Enrollment::factory()->forStudent($this->student)->forSection($s2)->dropped()->create();

    $s3 = Section::factory()->forCourse($courses[2])->create();
    Enrollment::factory()->forStudent($this->student)->forSection($s3)->withdrawn()->create();

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeFalse();
    expect($result['completed_courses'])->toHaveCount(1);
});

test('retake deduplication counts unique courses', function () {
    $course = Course::factory()->for($this->program)->create();

    $section1 = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($this->student)->forSection($section1)->completed()->create();

    $section2 = Section::factory()->forCourse($course)->create();
    Enrollment::factory()->forStudent($this->student)->forSection($section2)->completed()->create();

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeTrue();
    expect($result['completed_courses'])->toHaveCount(1);
    expect($result['total_courses'])->toBe(1);
});

test('inactive courses still count toward completion requirements', function () {
    $activeCourse = Course::factory()->for($this->program)->create(['is_active' => true]);
    $inactiveCourse = Course::factory()->for($this->program)->create(['is_active' => false]);

    $s1 = Section::factory()->forCourse($activeCourse)->create();
    Enrollment::factory()->forStudent($this->student)->forSection($s1)->completed()->create();

    $result = $this->checker->handle($this->student, $this->program);

    expect($result['eligible'])->toBeFalse();
    expect($result['total_courses'])->toBe(2);
    expect($result['remaining_courses'])->toHaveCount(1);
    expect($result['remaining_courses']->first()->id)->toBe($inactiveCourse->id);
});
