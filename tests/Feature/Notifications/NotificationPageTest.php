<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('authenticated user can access notifications page', function () {
    $user = User::factory()->asStudent()->create();

    $this->actingAs($user)
        ->get(route('notifications'))
        ->assertOk();
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('notifications'))
        ->assertRedirect(route('login'));
});

test('page shows notification title and message', function () {
    $user = User::factory()->asStudent()->create();

    DatabaseNotification::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => 'App\Notifications\GradePosted',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Grade Posted', 'message' => 'A grade was posted for Midterm in WLD-101.', 'url' => '/registration/grades', 'icon' => 'chart-bar'],
    ]);

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->assertSee('Grade Posted')
        ->assertSee('A grade was posted for Midterm in WLD-101.');
});

test('mark individual notification as read sets read_at', function () {
    $user = User::factory()->asStudent()->create();

    $notification = DatabaseNotification::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => 'App\Notifications\GradePosted',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Test', 'message' => 'Test message', 'url' => route('dashboard'), 'icon' => 'bell'],
    ]);

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->call('markAsRead', $notification->id)
        ->assertRedirect(route('dashboard'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all as read sets read_at on all unread', function () {
    $user = User::factory()->asStudent()->create();

    for ($i = 0; $i < 3; $i++) {
        DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\GradePosted',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['title' => 'Test', 'message' => 'Message', 'url' => '/', 'icon' => 'bell'],
        ]);
    }

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->call('markAllAsRead');

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('read notifications are still visible', function () {
    $user = User::factory()->asStudent()->create();

    DatabaseNotification::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => 'App\Notifications\GradePosted',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Read Notification', 'message' => 'Already read', 'url' => '/', 'icon' => 'bell'],
        'read_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->assertSee('Read Notification');
});

test('pagination works with many notifications', function () {
    $user = User::factory()->asStudent()->create();

    for ($i = 0; $i < 25; $i++) {
        DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\GradePosted',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['title' => "Notification {$i}", 'message' => 'Message', 'url' => '/', 'icon' => 'bell'],
        ]);
    }

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->assertSee('Next');
});

test('empty state shown when no notifications', function () {
    $user = User::factory()->asStudent()->create();

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->assertSee('No notifications yet');
});

test('different roles can access notifications page', function () {
    $admin = User::factory()->asAdmin()->create();
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($admin)->get(route('notifications'))->assertOk();
    $this->actingAs($instructor)->get(route('notifications'))->assertOk();
});
