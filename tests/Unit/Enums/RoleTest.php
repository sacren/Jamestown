<?php

use App\Enums\Role;

test('all roles have expected values', function () {
    expect(Role::SuperAdmin->value)->toBe('super-admin');
    expect(Role::Admin->value)->toBe('admin');
    expect(Role::Registrar->value)->toBe('registrar');
    expect(Role::Instructor->value)->toBe('instructor');
    expect(Role::Student->value)->toBe('student');
});

test('role enum has exactly five cases', function () {
    expect(Role::cases())->toHaveCount(5);
});
