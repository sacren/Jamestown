<?php

use App\Models\Announcement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access create page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.announcements.create'))
        ->assertOk();
});

test('registrar can access create page', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.announcements.create'))
        ->assertOk();
});

test('instructor is forbidden from create page', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.announcements.create'))
        ->assertForbidden();
});

test('student is forbidden from create page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.announcements.create'))
        ->assertForbidden();
});

test('can create and publish announcement', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'Welcome Students')
        ->set('body', 'Welcome to the new semester!')
        ->set('audience', 'students')
        ->call('save', true)
        ->assertRedirect(route('admin.announcements.index'));

    $announcement = Announcement::first();
    expect($announcement->title)->toBe('Welcome Students');
    expect($announcement->published_at)->not->toBeNull();
    expect($announcement->author_id)->toBe($admin->id);
});

test('can save announcement as draft', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'Draft Announcement')
        ->set('body', 'Coming soon')
        ->set('audience', 'all')
        ->call('save', false)
        ->assertRedirect(route('admin.announcements.index'));

    $announcement = Announcement::first();
    expect($announcement->published_at)->toBeNull();
});

test('validation error for missing title', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('body', 'Some body')
        ->set('audience', 'all')
        ->call('save', true)
        ->assertHasErrors(['title']);
});

test('validation error for missing body', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'Some title')
        ->set('audience', 'all')
        ->call('save', true)
        ->assertHasErrors(['body']);
});

test('validation error for invalid audience', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.create')
        ->set('title', 'Some title')
        ->set('body', 'Some body')
        ->set('audience', 'invalid')
        ->call('save', true)
        ->assertHasErrors(['audience']);
});
