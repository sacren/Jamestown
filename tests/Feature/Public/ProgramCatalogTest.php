<?php

use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

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

test('public catalog search filters by program name', function () {
    Program::factory()->create(['name' => 'Welding Technology', 'code' => 'WLD']);
    Program::factory()->create(['name' => 'HVAC Technology', 'code' => 'HVAC']);

    Livewire::test('pages::public.programs')
        ->set('search', 'Welding')
        ->assertSee('Welding Technology')
        ->assertDontSee('HVAC Technology');
});

test('public catalog search filters by program code', function () {
    Program::factory()->create(['name' => 'Welding Technology', 'code' => 'WLD']);
    Program::factory()->create(['name' => 'HVAC Technology', 'code' => 'HVAC']);

    Livewire::test('pages::public.programs')
        ->set('search', 'HVAC')
        ->assertSee('HVAC Technology')
        ->assertDontSee('Welding Technology');
});

test('public catalog duration filter excludes programs outside the bucket', function () {
    Program::factory()->create(['name' => 'Short Program', 'duration_weeks' => 12]);
    Program::factory()->create(['name' => 'Medium Program', 'duration_weeks' => 30]);
    Program::factory()->create(['name' => 'Long Program', 'duration_weeks' => 52]);

    Livewire::test('pages::public.programs')
        ->set('durationFilter', 'short')
        ->assertSee('Short Program')
        ->assertDontSee('Medium Program')
        ->assertDontSee('Long Program')
        ->set('durationFilter', 'medium')
        ->assertSee('Medium Program')
        ->assertDontSee('Short Program')
        ->assertDontSee('Long Program')
        ->set('durationFilter', 'long')
        ->assertSee('Long Program')
        ->assertDontSee('Short Program')
        ->assertDontSee('Medium Program');
});

test('public catalog cost filter excludes programs outside the bucket', function () {
    Program::factory()->create(['name' => 'Cheap Program', 'tuition_cost' => 3000]);
    Program::factory()->create(['name' => 'Mid Program', 'tuition_cost' => 10000]);
    Program::factory()->create(['name' => 'Premium Program', 'tuition_cost' => 20000]);

    Livewire::test('pages::public.programs')
        ->set('costFilter', 'under-5k')
        ->assertSee('Cheap Program')
        ->assertDontSee('Mid Program')
        ->assertDontSee('Premium Program')
        ->set('costFilter', '5k-15k')
        ->assertSee('Mid Program')
        ->assertDontSee('Cheap Program')
        ->assertDontSee('Premium Program')
        ->set('costFilter', 'over-15k')
        ->assertSee('Premium Program')
        ->assertDontSee('Cheap Program')
        ->assertDontSee('Mid Program');
});

test('public catalog combines search with duration filter', function () {
    Program::factory()->create(['name' => 'Welding Short', 'code' => 'WLDS', 'duration_weeks' => 12]);
    Program::factory()->create(['name' => 'Welding Long', 'code' => 'WLDL', 'duration_weeks' => 52]);
    Program::factory()->create(['name' => 'HVAC Short', 'code' => 'HVACS', 'duration_weeks' => 12]);

    Livewire::test('pages::public.programs')
        ->set('search', 'Welding')
        ->set('durationFilter', 'short')
        ->assertSee('Welding Short')
        ->assertDontSee('Welding Long')
        ->assertDontSee('HVAC Short');
});

test('public catalog shows empty state when no programs match filters', function () {
    Program::factory()->create(['name' => 'Welding Technology', 'duration_weeks' => 12]);

    Livewire::test('pages::public.programs')
        ->set('search', 'Carpentry')
        ->assertSee(__('No programs match your filters.'))
        ->assertDontSee('Welding Technology');
});

test('public catalog returns all active programs when filters are cleared', function () {
    Program::factory()->create(['name' => 'Welding Technology']);
    Program::factory()->create(['name' => 'HVAC Technology']);

    Livewire::test('pages::public.programs')
        ->set('search', 'Welding')
        ->assertDontSee('HVAC Technology')
        ->set('search', '')
        ->assertSee('Welding Technology')
        ->assertSee('HVAC Technology');
});
