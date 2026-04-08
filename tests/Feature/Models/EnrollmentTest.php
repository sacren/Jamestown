<?php

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('enrollment has fillable attributes', function () {
    $student = User::factory()->asStudent()->create();
    $section = Section::factory()->create();

    $enrollment = Enrollment::create([
        'user_id' => $student->id,
        'section_id' => $section->id,
        'status' => EnrollmentStatus::Enrolled,
        'enrolled_at' => now(),
    ]);

    expect($enrollment->user_id)->toBe($student->id);
    expect($enrollment->section_id)->toBe($section->id);
    expect($enrollment->status)->toBe(EnrollmentStatus::Enrolled);
});

test('enrollment belongs to a user', function () {
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->create();

    expect($enrollment->student)->toBeInstanceOf(User::class);
    expect($enrollment->student->id)->toBe($student->id);
});

test('enrollment belongs to a section', function () {
    $section = Section::factory()->create();
    $enrollment = Enrollment::factory()->forSection($section)->create();

    expect($enrollment->section)->toBeInstanceOf(Section::class);
    expect($enrollment->section->id)->toBe($section->id);
});

test('enrollment casts status to EnrollmentStatus enum', function () {
    $enrollment = Enrollment::factory()->create();

    expect($enrollment->status)->toBeInstanceOf(EnrollmentStatus::class);
});

test('section currentEnrollmentCount returns correct count', function () {
    $section = Section::factory()->create();
    $student1 = User::factory()->asStudent()->create();
    $student2 = User::factory()->asStudent()->create();
    $student3 = User::factory()->asStudent()->create();

    Enrollment::factory()->forSection($section)->forStudent($student1)->create();
    Enrollment::factory()->forSection($section)->forStudent($student2)->create();
    Enrollment::factory()->forSection($section)->forStudent($student3)->dropped()->create();

    expect($section->currentEnrollmentCount())->toBe(2);
});
