<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('bell shows unread count when notifications exist', function () {
    $user = User::factory()->asStudent()->create();

    for ($i = 0; $i < 3; $i++) {
        DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\GradePosted',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['title' => 'Test', 'message' => 'Message', 'url' => '/', 'icon' => 'bell'],
        ]);
    }

    Livewire::actingAs($user)
        ->test('notification-bell')
        ->assertSee('3');
});

test('bell hides count when no unread notifications', function () {
    $user = User::factory()->asStudent()->create();

    $html = Livewire::actingAs($user)
        ->test('notification-bell')
        ->html();

    expect($html)->not->toContain('bg-red-500');
});

test('bell renders for authenticated user', function () {
    $user = User::factory()->asStudent()->create();

    Livewire::actingAs($user)
        ->test('notification-bell')
        ->assertOk();
});

test('bell count reflects only unread notifications', function () {
    $user = User::factory()->asStudent()->create();

    DatabaseNotification::create([
        'id' => Str::uuid(),
        'type' => 'App\Notifications\GradePosted',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Unread', 'message' => 'Message', 'url' => '/', 'icon' => 'bell'],
    ]);

    DatabaseNotification::create([
        'id' => Str::uuid(),
        'type' => 'App\Notifications\GradePosted',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Read', 'message' => 'Message', 'url' => '/', 'icon' => 'bell'],
        'read_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('notification-bell')
        ->assertSee('1');
});
