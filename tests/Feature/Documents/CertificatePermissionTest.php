<?php

use App\Enums\Role;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('all certificate permissions exist after seeding', function () {
    $expected = [
        'certificates.view-any',
        'certificates.view-own',
        'certificates.manage',
    ];

    foreach ($expected as $name) {
        expect(Permission::where('name', $name)->exists())->toBeTrue();
    }
});

test('admin has all certificate permissions', function () {
    $admin = SpatieRole::findByName(Role::Admin->value);

    expect($admin->hasPermissionTo('certificates.view-any'))->toBeTrue();
    expect($admin->hasPermissionTo('certificates.view-own'))->toBeTrue();
    expect($admin->hasPermissionTo('certificates.manage'))->toBeTrue();
});

test('registrar has view-any and manage', function () {
    $registrar = SpatieRole::findByName(Role::Registrar->value);

    expect($registrar->hasPermissionTo('certificates.view-any'))->toBeTrue();
    expect($registrar->hasPermissionTo('certificates.manage'))->toBeTrue();
    expect($registrar->hasPermissionTo('certificates.view-own'))->toBeFalse();
});

test('instructor has zero certificate permissions', function () {
    $instructor = SpatieRole::findByName(Role::Instructor->value);

    $certPerms = ['certificates.view-any', 'certificates.view-own', 'certificates.manage'];
    foreach ($certPerms as $permission) {
        expect($instructor->hasPermissionTo($permission))->toBeFalse();
    }
});

test('student has only view-own certificate permission', function () {
    $student = SpatieRole::findByName(Role::Student->value);

    expect($student->hasPermissionTo('certificates.view-own'))->toBeTrue();
    expect($student->hasPermissionTo('certificates.view-any'))->toBeFalse();
    expect($student->hasPermissionTo('certificates.manage'))->toBeFalse();
});
