<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\Term;
use App\Models\User;

test('home page responds with 200 for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('home page uses the marketing layout', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false);
});

test('home hero renders tagline as the primary heading', function () {
    $response = $this->get(route('home'));

    $response->assertSeeInOrder(
        ['<h1', 'Skills that build empires.', '</h1>'],
        escape: false
    );
});

test('home hero shows register and sign in CTAs for guests', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee(__('Get started'), escape: false)
        ->assertSee(__('Sign in'), escape: false)
        ->assertSee(route('register'), escape: false)
        ->assertSee(route('login'), escape: false)
        ->assertDontSee(__('Go to dashboard'), escape: false);
});

test('home hero shows dashboard CTA for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('home'));

    $response
        ->assertSee(__('Go to dashboard'), escape: false)
        ->assertSee(route('dashboard'), escape: false)
        ->assertDontSee(__('Get started'), escape: false);
});

test('home page sets the meta description', function () {
    $response = $this->get(route('home'));

    $response->assertSee('<meta name="description"', escape: false);
});

test('home stats strip counts active programs and active courses only', function () {
    Program::factory()->count(3)->create(['is_active' => true]);
    Program::factory()->count(2)->create(['is_active' => false]);
    $program = Program::factory()->create(['is_active' => true]);
    Course::factory()->count(5)->forProgram($program)->create(['is_active' => true]);
    Course::factory()->count(2)->forProgram($program)->create(['is_active' => false]);

    $response = $this->get(route('home'));

    $response
        ->assertSeeInOrder(['Programs', '4'], escape: false)
        ->assertSeeInOrder(['Courses', '5'], escape: false);
});

test('home stats strip shows current term name when today falls inside an active term', function () {
    Term::factory()->create([
        'name' => 'Spring 2026',
        'start_date' => today()->subWeeks(2),
        'end_date' => today()->addWeeks(10),
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertSee('Spring 2026', escape: false);
});

test('home stats strip falls back to rolling enrollment when no active term matches today', function () {
    $response = $this->get(route('home'));

    $response->assertSee(__('Rolling enrollment'), escape: false);
});

test('home stats strip prefers the next upcoming active term when no term covers today', function () {
    Term::factory()->create([
        'name' => 'Past Term',
        'start_date' => today()->subMonths(6),
        'end_date' => today()->subMonths(2),
        'is_active' => true,
    ]);
    Term::factory()->create([
        'name' => 'Future Term',
        'start_date' => today()->addWeeks(4),
        'end_date' => today()->addMonths(4),
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertSee('Future Term', escape: false)
        ->assertDontSee('Past Term', escape: false);
});

test('home featured section renders up to three active programs', function () {
    Program::factory()->count(5)->create(['is_active' => true]);

    $response = $this->get(route('home'));

    expect(substr_count($response->getContent(), 'wire:key="program-card-'))->toBe(3);
});

test('home featured section excludes inactive programs', function () {
    Program::factory()->create(['name' => 'Active Program', 'is_active' => true]);
    Program::factory()->create(['name' => 'Hidden Program', 'is_active' => false]);

    $response = $this->get(route('home'));

    $response
        ->assertSee('Active Program')
        ->assertDontSee('Hidden Program');
});

test('home featured section orders programs by active course count then name', function () {
    $few = Program::factory()->create(['name' => 'Alpha Program', 'is_active' => true]);
    $many = Program::factory()->create(['name' => 'Zulu Program', 'is_active' => true]);

    Course::factory()->count(1)->forProgram($few)->create(['is_active' => true]);
    Course::factory()->count(4)->forProgram($many)->create(['is_active' => true]);

    $response = $this->get(route('home'));

    $response->assertSeeInOrder(['Zulu Program', 'Alpha Program'], escape: false);
});

test('home featured section shows empty state when no active programs exist', function () {
    Program::factory()->create(['is_active' => false]);

    $response = $this->get(route('home'));

    $response->assertSee(__('New programs coming soon.'), escape: false);
});
