<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access reports index', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk();
});

test('registrar can access reports index', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.reports.index'))
        ->assertOk();
});

test('reports index shows all report links', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.index')
        ->assertSee('Enrollment')
        ->assertSee('Attendance')
        ->assertSee('Grade Performance')
        ->assertSee('Financial')
        ->assertSee('Program Completion');
});
