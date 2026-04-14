<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access announcements index', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.announcements.index'))
        ->assertOk();
});

test('registrar can access announcements index', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.announcements.index'))
        ->assertOk();
});

test('instructor is forbidden from announcements index', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.announcements.index'))
        ->assertForbidden();
});

test('student is forbidden from announcements index', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.announcements.index'))
        ->assertForbidden();
});

test('lists published and draft announcements', function () {
    $admin = User::factory()->asAdmin()->create();
    $published = Announcement::factory()->forAuthor($admin)->create(['title' => 'Published One']);
    $draft = Announcement::factory()->draft()->forAuthor($admin)->create(['title' => 'Draft One']);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->assertSee('Published One')
        ->assertSee('Draft One');
});

test('search by title filters results', function () {
    $admin = User::factory()->asAdmin()->create();
    Announcement::factory()->forAuthor($admin)->create(['title' => 'Welcome Students']);
    Announcement::factory()->forAuthor($admin)->create(['title' => 'Grade Deadline']);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->set('search', 'Welcome')
        ->assertSee('Welcome Students')
        ->assertDontSee('Grade Deadline');
});

test('filter by published status shows only published', function () {
    $admin = User::factory()->asAdmin()->create();
    Announcement::factory()->forAuthor($admin)->create(['title' => 'Welcome Announcement', 'published_at' => now()->subDay()]);
    Announcement::factory()->draft()->forAuthor($admin)->create(['title' => 'Upcoming News']);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->set('statusFilter', 'published')
        ->assertSee('Welcome Announcement')
        ->assertDontSee('Upcoming News');
});

test('filter by draft status shows only drafts', function () {
    $admin = User::factory()->asAdmin()->create();
    Announcement::factory()->forAuthor($admin)->create(['title' => 'Welcome Announcement', 'published_at' => now()->subDay()]);
    Announcement::factory()->draft()->forAuthor($admin)->create(['title' => 'Upcoming News']);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->set('statusFilter', 'draft')
        ->assertSee('Upcoming News')
        ->assertDontSee('Welcome Announcement');
});

test('filter by scheduled status shows only scheduled', function () {
    $admin = User::factory()->asAdmin()->create();
    Announcement::factory()->forAuthor($admin)->create(['title' => 'Welcome Announcement', 'published_at' => now()->subDay()]);
    Announcement::factory()->scheduled()->forAuthor($admin)->create(['title' => 'Future Event']);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->set('statusFilter', 'scheduled')
        ->assertSee('Future Event')
        ->assertDontSee('Welcome Announcement');
});

test('soft delete removes announcement from list', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->forAuthor($admin)->create(['title' => 'To Delete']);

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->assertSee('To Delete')
        ->call('deleteAnnouncement', $announcement->id)
        ->assertDontSee('To Delete');

    expect(Announcement::withTrashed()->find($announcement->id))->not->toBeNull();
});

test('pagination works', function () {
    $admin = User::factory()->asAdmin()->create();
    Announcement::factory()->forAuthor($admin)->count(20)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.announcements.index')
        ->assertSee('Next');
});
