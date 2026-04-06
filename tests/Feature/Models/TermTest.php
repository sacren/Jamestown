<?php

use App\Models\Section;
use App\Models\Term;
use Carbon\CarbonImmutable;

test('term has fillable attributes', function () {
    $term = Term::factory()->create([
        'name' => 'Fall 2026',
        'code' => 'FA2026',
    ]);

    expect($term->name)->toBe('Fall 2026');
    expect($term->code)->toBe('FA2026');
});

test('term casts dates correctly', function () {
    $term = Term::factory()->create();

    expect($term->start_date)->toBeInstanceOf(CarbonImmutable::class);
    expect($term->end_date)->toBeInstanceOf(CarbonImmutable::class);
    expect($term->registration_start)->toBeInstanceOf(CarbonImmutable::class);
    expect($term->registration_end)->toBeInstanceOf(CarbonImmutable::class);
});

test('term casts is_active to boolean', function () {
    $term = Term::factory()->create(['is_active' => true]);

    expect($term->is_active)->toBeBool();
    expect($term->is_active)->toBeTrue();
});

test('term has many sections', function () {
    $term = Term::factory()->create();
    Section::factory()->forTerm($term)->create();

    expect($term->sections)->toHaveCount(1);
});

test('term activeSections only returns active sections', function () {
    $term = Term::factory()->create();
    Section::factory()->forTerm($term)->create(['is_active' => true]);
    Section::factory()->forTerm($term)->create(['is_active' => false]);

    expect($term->activeSections)->toHaveCount(1);
});
