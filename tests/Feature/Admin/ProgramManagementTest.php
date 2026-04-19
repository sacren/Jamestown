<?php

use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view programs listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.programs.index'))
        ->assertOk();
});

test('admin can search programs by name', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['name' => 'Welding Technology']);
    Program::factory()->create(['name' => 'HVAC Technology']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.index')
        ->set('search', 'Welding')
        ->assertSee('Welding Technology')
        ->assertDontSee('HVAC Technology');
});

test('admin can search programs by code', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['name' => 'Welding Technology', 'code' => 'WLD']);
    Program::factory()->create(['name' => 'HVAC Technology', 'code' => 'HVAC']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.index')
        ->set('search', 'WLD')
        ->assertSee('Welding Technology')
        ->assertDontSee('HVAC Technology');
});

test('admin can filter programs by active status', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['name' => 'Active Program', 'is_active' => true]);
    Program::factory()->create(['name' => 'Inactive Program', 'is_active' => false]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.index')
        ->set('statusFilter', 'active')
        ->assertSee('Active Program')
        ->assertDontSee('Inactive Program');
});

test('admin can view create program page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.programs.create'))
        ->assertOk();
});

test('admin can create a new program', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.create')
        ->set('name', 'Welding Technology')
        ->set('code', 'WLD')
        ->set('description', 'Learn welding.')
        ->set('duration_weeks', 36)
        ->set('total_credits_required', 45)
        ->set('tuition_cost', 15000)
        ->call('createProgram')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.programs.index'));

    $program = Program::where('code', 'WLD')->first();
    expect($program)->not->toBeNull();
    expect($program->name)->toBe('Welding Technology');
    expect($program->duration_weeks)->toBe(36);
    expect($program->slug)->toBe('welding-technology');
});

test('admin creating programs with duplicate names receives unique slugs', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.create')
        ->set('name', 'Welding Technology')
        ->set('code', 'WLDA')
        ->set('duration_weeks', 36)
        ->set('total_credits_required', 45)
        ->set('tuition_cost', 15000)
        ->call('createProgram')
        ->assertHasNoErrors();

    Livewire::test('pages::admin.programs.create')
        ->set('name', 'Welding Technology')
        ->set('code', 'WLDB')
        ->set('duration_weeks', 36)
        ->set('total_credits_required', 45)
        ->set('tuition_cost', 15000)
        ->call('createProgram')
        ->assertHasNoErrors();

    expect(Program::where('code', 'WLDA')->value('slug'))->toBe('welding-technology');
    expect(Program::where('code', 'WLDB')->value('slug'))->toBe('welding-technology-2');
});

test('create program validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.create')
        ->set('name', '')
        ->set('code', '')
        ->set('duration_weeks', null)
        ->set('total_credits_required', null)
        ->set('tuition_cost', null)
        ->call('createProgram')
        ->assertHasErrors(['name', 'code', 'duration_weeks', 'total_credits_required', 'tuition_cost']);
});

test('create program validates unique code', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['code' => 'WLD']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.create')
        ->set('name', 'Another Program')
        ->set('code', 'WLD')
        ->set('duration_weeks', 36)
        ->set('total_credits_required', 45)
        ->set('tuition_cost', 15000)
        ->call('createProgram')
        ->assertHasErrors(['code']);
});

test('admin can view edit program page', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.programs.edit', $program))
        ->assertOk();
});

test('admin can update a program', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['name' => 'Welding Technology']);
    $originalSlug = $program->slug;

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.edit', ['program' => $program])
        ->set('name', 'Updated Program')
        ->set('code', 'UPD')
        ->set('duration_weeks', 48)
        ->set('total_credits_required', 60)
        ->set('tuition_cost', 20000)
        ->call('updateProgram')
        ->assertHasNoErrors();

    $program->refresh();
    expect($program->name)->toBe('Updated Program');
    expect($program->code)->toBe('UPD');
    expect($program->duration_weeks)->toBe(48);
    expect($program->slug)->toBe($originalSlug);
});

test('update program validates unique code excluding self', function () {
    $admin = User::factory()->asAdmin()->create();
    Program::factory()->create(['code' => 'WLD']);
    $program = Program::factory()->create(['code' => 'HVAC']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.edit', ['program' => $program])
        ->set('code', 'WLD')
        ->call('updateProgram')
        ->assertHasErrors(['code']);
});

test('admin can toggle program active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.index')
        ->call('toggleActive', $program->id);

    $program->refresh();
    expect($program->is_active)->toBeFalse();
});

test('admin can delete a program', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.programs.edit', ['program' => $program])
        ->call('deleteProgram')
        ->assertRedirect(route('admin.programs.index'));

    expect(Program::find($program->id))->toBeNull();
});

test('student cannot access program management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.programs.index'))
        ->assertForbidden();
});

test('instructor cannot access program management', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.programs.index'))
        ->assertForbidden();
});

test('guest is redirected to login from program management', function () {
    $this->get(route('admin.programs.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access program management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.programs.index'))
        ->assertOk();
});
