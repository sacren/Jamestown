<?php

use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('guest can view public program catalog', function () {
    $this->get(route('public.programs'))->assertOk();
});

test('authenticated user can view public program catalog', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('public.programs'))
        ->assertOk();
});

test('public catalog only shows active programs', function () {
    Program::factory()->create(['name' => 'Active Program', 'is_active' => true]);
    Program::factory()->create(['name' => 'Inactive Program', 'is_active' => false]);

    $response = $this->get(route('public.programs'));

    $response->assertSee('Active Program');
    $response->assertDontSee('Inactive Program');
});

test('public catalog renders program details on cards', function () {
    Program::factory()->create([
        'name' => 'Welding Technology',
        'code' => 'WLD',
        'tuition_cost' => 15000.00,
    ]);

    $response = $this->get(route('public.programs'));

    $response->assertSee('Welding Technology');
    $response->assertSee('WLD');
    $response->assertSee('$15,000.00');
});

test('publicProgram binding resolves an active program by slug', function () {
    Route::middleware('web')->get(
        '/_testing/programs/{publicProgram:slug}',
        fn (Program $publicProgram) => $publicProgram->name
    );

    $program = Program::factory()->create(['name' => 'Welding Technology', 'is_active' => true]);

    $this->get('/_testing/programs/'.$program->slug)
        ->assertOk()
        ->assertSee('Welding Technology');
});

test('publicProgram binding rejects an inactive program with 404', function () {
    Route::middleware('web')->get(
        '/_testing/programs/{publicProgram:slug}',
        fn (Program $publicProgram) => $publicProgram->name
    );

    $program = Program::factory()->create(['is_active' => false]);

    $this->get('/_testing/programs/'.$program->slug)->assertNotFound();
});

test('publicProgram binding returns 404 for an unknown slug', function () {
    Route::middleware('web')->get(
        '/_testing/programs/{publicProgram:slug}',
        fn (Program $publicProgram) => $publicProgram->name
    );

    $this->get('/_testing/programs/does-not-exist')->assertNotFound();
});
