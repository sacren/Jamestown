<?php

use App\Enums\Role;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('all billing permissions exist after seeding', function () {
    $expected = [
        'invoices.view-any',
        'invoices.view-own',
        'invoices.manage',
        'payments.create',
        'payments.delete',
    ];

    foreach ($expected as $name) {
        expect(Permission::where('name', $name)->exists())->toBeTrue();
    }
});

test('admin role has full billing permissions', function () {
    $admin = SpatieRole::findByName(Role::Admin->value);

    expect($admin->hasPermissionTo('invoices.view-any'))->toBeTrue();
    expect($admin->hasPermissionTo('invoices.manage'))->toBeTrue();
    expect($admin->hasPermissionTo('payments.create'))->toBeTrue();
    expect($admin->hasPermissionTo('payments.delete'))->toBeTrue();
});

test('registrar has view/manage/create but cannot delete payments', function () {
    $registrar = SpatieRole::findByName(Role::Registrar->value);

    expect($registrar->hasPermissionTo('invoices.view-any'))->toBeTrue();
    expect($registrar->hasPermissionTo('invoices.manage'))->toBeTrue();
    expect($registrar->hasPermissionTo('payments.create'))->toBeTrue();
    expect($registrar->hasPermissionTo('payments.delete'))->toBeFalse();
});

test('instructor has zero billing permissions', function () {
    $instructor = SpatieRole::findByName(Role::Instructor->value);

    $billing = ['invoices.view-any', 'invoices.view-own', 'invoices.manage', 'payments.create', 'payments.delete'];
    foreach ($billing as $permission) {
        expect($instructor->hasPermissionTo($permission))->toBeFalse();
    }
});

test('student has only view-own billing permission', function () {
    $student = SpatieRole::findByName(Role::Student->value);

    expect($student->hasPermissionTo('invoices.view-own'))->toBeTrue();
    expect($student->hasPermissionTo('invoices.view-any'))->toBeFalse();
    expect($student->hasPermissionTo('invoices.manage'))->toBeFalse();
    expect($student->hasPermissionTo('payments.create'))->toBeFalse();
    expect($student->hasPermissionTo('payments.delete'))->toBeFalse();
});
