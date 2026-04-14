<?php

use App\Models\Announcement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access edit page', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create();

    $this->actingAs($admin)
        ->get(route('admin.announcements.edit', $announcement))
        ->assertOk();
});

test('registrar can access edit page', function () {
    $registrar = User::factory()->asRegistrar()->create();
    $announcement = Announcement::factory()->forAuthor($registrar)->create();

    $this->actingAs($registrar)
        ->get(route('admin.announcements.edit', $announcement))
        ->assertOk();
});

test('instructor is forbidden from edit page', function () {
    $instructor = User::factory()->asInstructor()->create();
    $announcement = Announcement::factory()->create();

    $this->actingAs($instructor)
        ->get(route('admin.announcements.edit', $announcement))
        ->assertForbidden();
});

test('student is forbidden from edit page', function () {
    $student = User::factory()->asStudent()->create();
    $announcement = Announcement::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.announcements.edit', $announcement))
        ->assertForbidden();
});

test('form is pre-populated with existing data', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create([
        'title' => 'Original Title',
        'body' => 'Original Body',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement])
        ->assertSet('title', 'Original Title')
        ->assertSet('body', 'Original Body');
});

test('can update title body and audience', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement])
        ->set('title', 'Updated Title')
        ->set('body', 'Updated Body')
        ->set('audience', 'instructors')
        ->call('save')
        ->assertRedirect(route('admin.announcements.index'));

    $announcement->refresh();
    expect($announcement->title)->toBe('Updated Title');
    expect($announcement->body)->toBe('Updated Body');
    expect($announcement->audience->value)->toBe('instructors');
});

test('can publish a draft', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->draft()->forAuthor($admin)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement])
        ->call('publish')
        ->assertRedirect(route('admin.announcements.index'));

    $announcement->refresh();
    expect($announcement->published_at)->not->toBeNull();
});

test('can unpublish a published announcement', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create(['published_at' => now()->subDay()]);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement])
        ->call('unpublish')
        ->assertRedirect(route('admin.announcements.index'));

    $announcement->refresh();
    expect($announcement->published_at)->toBeNull();
});

test('validation rejects empty title on save', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.edit', ['announcement' => $announcement])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('soft-deleted announcement returns 404', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create();
    $announcement->delete();

    $this->actingAs($admin)
        ->get(route('admin.announcements.edit', $announcement->id))
        ->assertNotFound();
});
