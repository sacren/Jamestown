<?php

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('program has fillable attributes', function () {
    $program = Program::factory()->create([
        'name' => 'Welding Technology',
        'code' => 'WLD',
    ]);

    expect($program->name)->toBe('Welding Technology');
    expect($program->code)->toBe('WLD');
});

test('program casts is_active to boolean', function () {
    $program = Program::factory()->create(['is_active' => true]);

    expect($program->is_active)->toBeTrue();
    expect($program->is_active)->toBeBool();
});

test('program casts tuition_cost to decimal', function () {
    $program = Program::factory()->create(['tuition_cost' => 15000.50]);

    expect($program->tuition_cost)->toBe('15000.50');
});

test('program has many courses', function () {
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->count(3)->create();

    expect($program->courses)->toHaveCount(3);
});

test('program activeCourses only returns active courses', function () {
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->count(2)->create(['is_active' => true]);
    Course::factory()->forProgram($program)->create(['is_active' => false]);

    expect($program->activeCourses)->toHaveCount(2);
});

test('deleting program cascades to courses', function () {
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->count(3)->create();

    expect(Course::where('program_id', $program->id)->count())->toBe(3);

    $program->delete();

    expect(Course::where('program_id', $program->id)->count())->toBe(0);
});

test('programs table has a nullable slug column', function () {
    expect(Schema::hasColumn('programs', 'slug'))->toBeTrue();

    $program = Program::factory()->make(['slug' => null]);
    $program->saveQuietly();

    expect($program->fresh()->slug)->toBeNull();
});

test('programs table enforces unique slug constraint', function () {
    Program::factory()->create()->forceFill(['slug' => 'welding-technology'])->save();

    Program::factory()->create()->forceFill(['slug' => 'welding-technology'])->save();
})->throws(QueryException::class);

test('program auto-generates slug from name when slug is not provided', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology', 'slug' => null]);

    expect($program->slug)->toBe('welding-technology');
});

test('program does not overwrite an explicitly provided slug', function () {
    $program = Program::factory()->create([
        'name' => 'Welding Technology',
        'slug' => 'custom-slug',
    ]);

    expect($program->slug)->toBe('custom-slug');
});

test('program appends numeric suffix when generated slug collides', function () {
    Program::factory()->create(['name' => 'Welding Technology', 'slug' => null]);
    $second = Program::factory()->create(['name' => 'Welding Technology', 'slug' => null]);
    $third = Program::factory()->create(['name' => 'Welding Technology', 'slug' => null]);

    expect($second->slug)->toBe('welding-technology-2');
    expect($third->slug)->toBe('welding-technology-3');
});

test('program slug stays stable when name changes after creation', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology', 'slug' => null]);

    $program->update(['name' => 'Advanced Welding']);

    expect($program->fresh()->slug)->toBe('welding-technology');
});

test('program factory populates slug derived from the name', function () {
    $program = Program::factory()->create();

    expect($program->slug)
        ->not->toBeNull()
        ->and($program->slug)->toBe(Str::slug($program->name));
});
