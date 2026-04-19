<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('guest can view public program detail page by slug', function () {
    $program = Program::factory()->create([
        'name' => 'Welding Technology',
        'code' => 'WLD',
    ]);

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertSee('Welding Technology')
        ->assertSee('WLD');
});

test('authenticated user can view public program detail page', function () {
    $program = Program::factory()->create(['name' => 'HVAC Technology']);
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('public.program', $program))
        ->assertOk()
        ->assertSee('HVAC Technology');
});

test('public program detail page returns 404 for inactive programs', function () {
    $program = Program::factory()->create(['is_active' => false]);

    $this->get(route('public.program', $program))->assertNotFound();
});

test('public program detail page renders core program details', function () {
    $program = Program::factory()->create([
        'name' => 'Welding Technology',
        'code' => 'WLD',
        'description' => 'Hands-on welding training for industrial trades.',
        'duration_weeks' => 40,
        'total_credits_required' => 60,
        'tuition_cost' => 15000.00,
    ]);

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertSee('Welding Technology')
        ->assertSee('WLD')
        ->assertSee('Hands-on welding training')
        ->assertSee('40')
        ->assertSee('60')
        ->assertSee('$15,000.00');
});

test('public program detail page shows breadcrumbs back to home and catalog', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);

    $response = $this->get(route('public.program', $program));

    $response
        ->assertOk()
        ->assertSee('aria-label="'.__('Breadcrumb').'"', false)
        ->assertSee(__('Home'))
        ->assertSee(__('Programs'))
        ->assertSee(route('home'))
        ->assertSee(route('public.programs'));
});

test('public program detail page lists active courses with prerequisites', function () {
    $program = Program::factory()->create();
    $prereq = Course::factory()->create(['program_id' => $program->id, 'code' => 'WLD100', 'is_active' => true]);
    $course = Course::factory()->create([
        'program_id' => $program->id,
        'code' => 'WLD200',
        'name' => 'Advanced Welding',
        'is_active' => true,
    ]);
    $course->prerequisites()->attach($prereq);

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertSee('WLD200')
        ->assertSee('Advanced Welding')
        ->assertSee('WLD100');
});

test('public program detail page hides inactive courses', function () {
    $program = Program::factory()->create();
    Course::factory()->create([
        'program_id' => $program->id,
        'name' => 'Retired Course',
        'is_active' => false,
    ]);

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertDontSee('Retired Course');
});

test('public program detail page shows an empty-courses notice when none are active', function () {
    $program = Program::factory()->create();

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertSee(__('Course details will be published soon.'));
});

test('public program detail page links to register as the application CTA', function () {
    $program = Program::factory()->create();

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertSee(__('Apply to this program'))
        ->assertSee(route('register'));
});

test('public catalog program cards link to the public slug detail route', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);

    $this->get(route('public.programs'))
        ->assertOk()
        ->assertSee(route('public.program', $program));
});
