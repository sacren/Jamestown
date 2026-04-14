<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('manage-announcements permission exists', function () {
    expect(Permission::where('name', 'manage-announcements')->exists())->toBeTrue();
});

test('admin has manage-announcements permission', function () {
    $admin = User::factory()->asAdmin()->create();

    expect($admin->can('manage-announcements'))->toBeTrue();
});

test('registrar has manage-announcements permission', function () {
    $registrar = User::factory()->asRegistrar()->create();

    expect($registrar->can('manage-announcements'))->toBeTrue();
});

test('instructor does not have manage-announcements permission', function () {
    $instructor = User::factory()->asInstructor()->create();

    expect($instructor->can('manage-announcements'))->toBeFalse();
});

test('student does not have manage-announcements permission', function () {
    $student = User::factory()->asStudent()->create();

    expect($student->can('manage-announcements'))->toBeFalse();
});
