<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::registration());
    $this->seed(RoleAndPermissionSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('newly registered user has student role', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'newuser@example.com')->first();

    expect($user->hasRole(Role::Student))->toBeTrue();
});

test('newly registered user has student profile', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'newuser2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'newuser2@example.com')->first();

    expect($user->studentProfile)->not->toBeNull();
    expect($user->studentProfile->student_id_number)->toStartWith('STU-');
});
