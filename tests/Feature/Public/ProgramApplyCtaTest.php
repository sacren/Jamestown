<?php

use App\Models\Program;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('apply CTA flashes interested program id and redirects to register', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);

    Livewire::test('pages::public.program', ['publicProgram' => $program])
        ->call('apply')
        ->assertRedirect(route('register'));

    expect(session('interested_program'))->toBe($program->id);
});

test('register view shows interested program banner when session flash is set', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);

    $this->withSession(['interested_program' => $program->id])
        ->get(route('register'))
        ->assertOk()
        ->assertSee('data-test="interested-program-banner"', false)
        ->assertSee(__('Applying to :name', ['name' => 'Welding Technology']));
});

test('register view hides banner when no interested program is flashed', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('data-test="interested-program-banner"', false);
});

test('register view hides banner when interested program id is invalid', function () {
    $this->withSession(['interested_program' => 999999])
        ->get(route('register'))
        ->assertOk()
        ->assertDontSee('data-test="interested-program-banner"', false);
});

test('register view hides banner when interested program is inactive', function () {
    $program = Program::factory()->create(['is_active' => false]);

    $this->withSession(['interested_program' => $program->id])
        ->get(route('register'))
        ->assertOk()
        ->assertDontSee('data-test="interested-program-banner"', false);
});

test('register view reflashes interested program so banner survives validation errors', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);

    $response = $this->withSession(['interested_program' => $program->id])
        ->get(route('register'))
        ->assertOk();

    $response->assertSessionHas('interested_program', $program->id);
});
