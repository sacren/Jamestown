<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('view-reports permission exists', function () {
    expect(Permission::where('name', 'view-reports')->exists())->toBeTrue();
});

test('admin has view-reports permission', function () {
    $admin = User::factory()->asAdmin()->create();

    expect($admin->can('view-reports'))->toBeTrue();
});

test('registrar has view-reports permission', function () {
    $registrar = User::factory()->asRegistrar()->create();

    expect($registrar->can('view-reports'))->toBeTrue();
});

test('super admin can access reports index', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.reports.index'))
        ->assertOk();
});

test('instructor cannot access reports index', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

test('student cannot access reports index', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});
