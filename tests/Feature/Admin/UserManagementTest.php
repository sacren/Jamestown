<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view user listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('admin can search users by name', function () {
    $admin = User::factory()->asAdmin()->create();
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Smith']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.index')
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('admin can filter users by role', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create(['name' => 'Student User']);
    $instructor = User::factory()->asInstructor()->create(['name' => 'Instructor User']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.index')
        ->set('roleFilter', Role::Student->value)
        ->assertSee('Student User')
        ->assertDontSee('Instructor User');
});

test('admin can view create user page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk();
});

test('admin can create a new student user', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'New Student')
        ->set('email', 'newstudent@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Student->value)
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'newstudent@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole(Role::Student))->toBeTrue();
    expect($user->studentProfile)->not->toBeNull();
});

test('admin can create a new instructor user', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'New Instructor')
        ->set('email', 'newinstructor@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Instructor->value)
        ->set('bio', 'Expert welder')
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'newinstructor@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole(Role::Instructor))->toBeTrue();
    expect($user->instructorProfile)->not->toBeNull();
});

test('admin can create a new admin user', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'New Admin')
        ->set('email', 'newadmin@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Admin->value)
        ->set('department', 'Administration')
        ->set('title', 'Assistant Admin')
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'newadmin@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole(Role::Admin))->toBeTrue();
    expect($user->staffProfile)->not->toBeNull();
});

test('admin can view edit user page', function () {
    $admin = User::factory()->asAdmin()->create();
    $user = User::factory()->asStudent()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $user))
        ->assertOk();
});

test('admin can update user information', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $student])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('updateUser')
        ->assertHasNoErrors();

    $student->refresh();
    expect($student->name)->toBe('Updated Name');
    expect($student->email)->toBe('updated@example.com');
});

test('admin can change user role', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $student])
        ->set('role', Role::Instructor->value)
        ->call('updateUser')
        ->assertHasNoErrors();

    $student->refresh();
    expect($student->hasRole(Role::Instructor))->toBeTrue();
    expect($student->hasRole(Role::Student))->toBeFalse();
});

test('admin cannot change own role', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $admin])
        ->set('name', 'Updated Admin')
        ->call('updateUser')
        ->assertHasNoErrors();

    $admin->refresh();
    expect($admin->name)->toBe('Updated Admin');
    expect($admin->hasRole(Role::Admin))->toBeTrue();
});

test('admin can delete a user', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $student])
        ->call('deleteUser')
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($student->id))->toBeNull();
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.edit', ['user' => $admin])
        ->call('deleteUser')
        ->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});

test('student cannot access user management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('instructor cannot access user management', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('guest is redirected to login', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access user management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('create user validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', '')
        ->set('email', '')
        ->set('password', '')
        ->set('role', '')
        ->call('createUser')
        ->assertHasErrors(['name', 'email', 'password', 'role']);
});

test('create user validates unique email', function () {
    $admin = User::factory()->asAdmin()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.create')
        ->set('name', 'Test')
        ->set('email', 'taken@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Student->value)
        ->call('createUser')
        ->assertHasErrors(['email']);
});
