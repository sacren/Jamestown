<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('authenticated user can view program catalog', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('catalog.programs'))
        ->assertOk();
});

test('catalog only shows active programs', function () {
    $student = User::factory()->asStudent()->create();
    Program::factory()->create(['name' => 'Active Program', 'is_active' => true]);
    Program::factory()->create(['name' => 'Inactive Program', 'is_active' => false]);

    $this->actingAs($student);

    $response = $this->get(route('catalog.programs'));

    $response->assertSee('Active Program');
    $response->assertDontSee('Inactive Program');
});

test('catalog shows program details', function () {
    $student = User::factory()->asStudent()->create();
    Program::factory()->create([
        'name' => 'Welding Technology',
        'code' => 'WLD',
        'tuition_cost' => 15000.00,
    ]);

    $this->actingAs($student);

    $response = $this->get(route('catalog.programs'));

    $response->assertSee('Welding Technology');
    $response->assertSee('WLD');
    $response->assertSee('$15,000.00');
});

test('authenticated user can view program detail page', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create(['is_active' => true]);

    $this->actingAs($student)
        ->get(route('catalog.program', $program))
        ->assertOk();
});

test('program detail shows active courses only', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create(['is_active' => true]);
    Course::factory()->forProgram($program)->create(['name' => 'Active Course', 'is_active' => true]);
    Course::factory()->forProgram($program)->create(['name' => 'Inactive Course', 'is_active' => false]);

    $this->actingAs($student);

    $response = $this->get(route('catalog.program', $program));

    $response->assertSee('Active Course');
    $response->assertDontSee('Inactive Course');
});

test('program detail shows course prerequisites', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create(['is_active' => true]);
    $intro = Course::factory()->forProgram($program)->create(['name' => 'Intro', 'code' => 'WLD-101']);
    $advanced = Course::factory()->forProgram($program)->create(['name' => 'Advanced', 'code' => 'WLD-201']);
    $advanced->prerequisites()->attach($intro->id);

    $this->actingAs($student);

    $response = $this->get(route('catalog.program', $program));

    $response->assertSee('WLD-101');
    $response->assertSee('WLD-201');
});

test('guest is redirected to login from catalog', function () {
    $this->get(route('catalog.programs'))
        ->assertRedirect(route('login'));
});

test('inactive program returns 404 on detail page', function () {
    $student = User::factory()->asStudent()->create();
    $program = Program::factory()->create(['is_active' => false]);

    $this->actingAs($student)
        ->get(route('catalog.program', $program))
        ->assertNotFound();
});

test('all roles can view catalog', function () {
    $admin = User::factory()->asAdmin()->create();
    $instructor = User::factory()->asInstructor()->create();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($admin)->get(route('catalog.programs'))->assertOk();
    $this->actingAs($instructor)->get(route('catalog.programs'))->assertOk();
    $this->actingAs($student)->get(route('catalog.programs'))->assertOk();
});
