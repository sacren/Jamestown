<?php

use App\Enums\RoomType;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view rooms listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.rooms.index'))
        ->assertOk();
});

test('admin can search rooms by name', function () {
    $admin = User::factory()->asAdmin()->create();
    Room::factory()->create(['name' => 'Welding Shop A']);
    Room::factory()->create(['name' => 'Classroom 101']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.index')
        ->set('search', 'Welding')
        ->assertSee('Welding Shop A')
        ->assertDontSee('Classroom 101');
});

test('admin can search rooms by code', function () {
    $admin = User::factory()->asAdmin()->create();
    Room::factory()->create(['name' => 'Welding Shop A', 'code' => 'WS-A']);
    Room::factory()->create(['name' => 'Classroom 101', 'code' => 'CR-101']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.index')
        ->set('search', 'WS-A')
        ->assertSee('Welding Shop A')
        ->assertDontSee('Classroom 101');
});

test('admin can filter rooms by type', function () {
    $admin = User::factory()->asAdmin()->create();
    Room::factory()->shop()->create(['name' => 'Welding Shop']);
    Room::factory()->classroom()->create(['name' => 'Classroom 101']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.index')
        ->set('typeFilter', 'shop')
        ->assertSee('Welding Shop')
        ->assertDontSee('Classroom 101');
});

test('admin can filter rooms by active status', function () {
    $admin = User::factory()->asAdmin()->create();
    Room::factory()->create(['name' => 'Active Room', 'is_active' => true]);
    Room::factory()->create(['name' => 'Inactive Room', 'is_active' => false]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.index')
        ->set('statusFilter', 'active')
        ->assertSee('Active Room')
        ->assertDontSee('Inactive Room');
});

test('admin can view create room page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.rooms.create'))
        ->assertOk();
});

test('admin can create a new room', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.create')
        ->set('name', 'Welding Shop A')
        ->set('code', 'WS-A')
        ->set('building', 'Trade Building')
        ->set('capacity', 20)
        ->set('type', 'shop')
        ->call('createRoom')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.rooms.index'));

    $room = Room::where('code', 'WS-A')->first();
    expect($room)->not->toBeNull();
    expect($room->name)->toBe('Welding Shop A');
    expect($room->type)->toBe(RoomType::Shop);
});

test('create room validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.create')
        ->set('name', '')
        ->set('code', '')
        ->set('building', '')
        ->set('capacity', null)
        ->set('type', '')
        ->call('createRoom')
        ->assertHasErrors(['name', 'code', 'building', 'capacity', 'type']);
});

test('create room validates unique code', function () {
    $admin = User::factory()->asAdmin()->create();
    Room::factory()->create(['code' => 'WS-A']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.create')
        ->set('name', 'Another Shop')
        ->set('code', 'WS-A')
        ->set('building', 'Trade Building')
        ->set('capacity', 20)
        ->set('type', 'shop')
        ->call('createRoom')
        ->assertHasErrors(['code']);
});

test('admin can view edit room page', function () {
    $admin = User::factory()->asAdmin()->create();
    $room = Room::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.rooms.edit', $room))
        ->assertOk();
});

test('admin can update a room', function () {
    $admin = User::factory()->asAdmin()->create();
    $room = Room::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.edit', ['room' => $room])
        ->set('name', 'Updated Room')
        ->set('code', 'UPD-01')
        ->call('updateRoom')
        ->assertHasNoErrors();

    $room->refresh();
    expect($room->name)->toBe('Updated Room');
    expect($room->code)->toBe('UPD-01');
});

test('admin can toggle room active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $room = Room::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.index')
        ->call('toggleActive', $room->id);

    $room->refresh();
    expect($room->is_active)->toBeFalse();
});

test('admin can delete a room', function () {
    $admin = User::factory()->asAdmin()->create();
    $room = Room::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.rooms.edit', ['room' => $room])
        ->call('deleteRoom')
        ->assertRedirect(route('admin.rooms.index'));

    expect(Room::find($room->id))->toBeNull();
});

test('student cannot access room management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.rooms.index'))
        ->assertForbidden();
});

test('registrar cannot access room management', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.rooms.index'))
        ->assertForbidden();
});

test('guest is redirected to login from room management', function () {
    $this->get(route('admin.rooms.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access room management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.rooms.index'))
        ->assertOk();
});
