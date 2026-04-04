<?php

use App\Enums\Role;
use App\Models\InstructorProfile;
use App\Models\StaffProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('user can be assigned a role', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Student);

    expect($user->hasRole(Role::Student))->toBeTrue();
});

test('isStudent returns true for student role', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Student);

    expect($user->isStudent())->toBeTrue();
    expect($user->isInstructor())->toBeFalse();
});

test('isInstructor returns true for instructor role', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Instructor);

    expect($user->isInstructor())->toBeTrue();
    expect($user->isStudent())->toBeFalse();
});

test('isAdmin returns true for admin and super-admin roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Role::SuperAdmin);

    expect($admin->isAdmin())->toBeTrue();
    expect($superAdmin->isAdmin())->toBeTrue();
});

test('isSuperAdmin returns true only for super-admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Role::SuperAdmin);

    expect($admin->isSuperAdmin())->toBeFalse();
    expect($superAdmin->isSuperAdmin())->toBeTrue();
});

test('user has student profile relationship', function () {
    $user = User::factory()->create();
    StudentProfile::factory()->create(['user_id' => $user->id]);

    expect($user->studentProfile)->toBeInstanceOf(StudentProfile::class);
});

test('user has instructor profile relationship', function () {
    $user = User::factory()->create();
    InstructorProfile::factory()->create(['user_id' => $user->id]);

    expect($user->instructorProfile)->toBeInstanceOf(InstructorProfile::class);
});

test('user has staff profile relationship', function () {
    $user = User::factory()->create();
    StaffProfile::factory()->create(['user_id' => $user->id]);

    expect($user->staffProfile)->toBeInstanceOf(StaffProfile::class);
});
