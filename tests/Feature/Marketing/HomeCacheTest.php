<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\Term;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('home.program-count');
    Cache::forget('home.course-count');
    Cache::forget('home.current-term');
    Cache::forget('home.featured-programs');
});

test('home page warms all four cache keys on first render', function () {
    Term::factory()->create([
        'start_date' => today()->subWeek(),
        'end_date' => today()->addWeeks(10),
        'is_active' => true,
    ]);
    Cache::forget('home.current-term');

    expect(Cache::has('home.program-count'))->toBeFalse();
    expect(Cache::has('home.course-count'))->toBeFalse();
    expect(Cache::has('home.current-term'))->toBeFalse();
    expect(Cache::has('home.featured-programs'))->toBeFalse();

    $this->get(route('home'));

    expect(Cache::has('home.program-count'))->toBeTrue();
    expect(Cache::has('home.course-count'))->toBeTrue();
    expect(Cache::has('home.current-term'))->toBeTrue();
    expect(Cache::has('home.featured-programs'))->toBeTrue();
});

test('saving a program invalidates program count and featured programs cache', function () {
    Cache::put('home.program-count', 999, 600);
    Cache::put('home.featured-programs', collect(), 600);
    Cache::put('home.course-count', 42, 600);

    Program::factory()->create();

    expect(Cache::has('home.program-count'))->toBeFalse();
    expect(Cache::has('home.featured-programs'))->toBeFalse();
    expect(Cache::get('home.course-count'))->toBe(42);
});

test('deleting a program invalidates program count and featured programs cache', function () {
    $program = Program::factory()->create();
    Cache::put('home.program-count', 999, 600);
    Cache::put('home.featured-programs', collect(), 600);

    $program->delete();

    expect(Cache::has('home.program-count'))->toBeFalse();
    expect(Cache::has('home.featured-programs'))->toBeFalse();
});

test('saving a course invalidates course count and featured programs cache', function () {
    $program = Program::factory()->create();
    Cache::put('home.course-count', 999, 600);
    Cache::put('home.featured-programs', collect(), 600);
    Cache::put('home.program-count', 42, 600);

    Course::factory()->forProgram($program)->create();

    expect(Cache::has('home.course-count'))->toBeFalse();
    expect(Cache::has('home.featured-programs'))->toBeFalse();
    expect(Cache::get('home.program-count'))->toBe(42);
});

test('saving a term invalidates current term cache only', function () {
    Cache::put('home.current-term', 'stale', 600);
    Cache::put('home.program-count', 42, 600);

    Term::factory()->create();

    expect(Cache::has('home.current-term'))->toBeFalse();
    expect(Cache::get('home.program-count'))->toBe(42);
});

test('deleting a term invalidates current term cache', function () {
    $term = Term::factory()->create();
    Cache::put('home.current-term', 'stale', 600);

    $term->delete();

    expect(Cache::has('home.current-term'))->toBeFalse();
});

test('home page reflects new active program after observer invalidates cache', function () {
    $this->get(route('home'));

    expect(Cache::has('home.program-count'))->toBeTrue();

    Program::factory()->create(['name' => 'Welding Technology', 'is_active' => true]);

    $this->get(route('home'))->assertSee('Welding Technology');
});
