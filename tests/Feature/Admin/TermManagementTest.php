<?php

use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view terms listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.terms.index'))
        ->assertOk();
});

test('admin can search terms by name', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['name' => 'Fall 2026']);
    Term::factory()->create(['name' => 'Spring 2027']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.index')
        ->set('search', 'Fall')
        ->assertSee('Fall 2026')
        ->assertDontSee('Spring 2027');
});

test('admin can search terms by code', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['name' => 'Fall 2026', 'code' => 'FA2026']);
    Term::factory()->create(['name' => 'Spring 2027', 'code' => 'SP2027']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.index')
        ->set('search', 'FA2026')
        ->assertSee('Fall 2026')
        ->assertDontSee('Spring 2027');
});

test('admin can filter terms by active status', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['name' => 'Active Term', 'is_active' => true]);
    Term::factory()->create(['name' => 'Inactive Term', 'is_active' => false]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.index')
        ->set('statusFilter', 'active')
        ->assertSee('Active Term')
        ->assertDontSee('Inactive Term');
});

test('admin can view create term page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.terms.create'))
        ->assertOk();
});

test('admin can create a new term', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.create')
        ->set('name', 'Fall 2026')
        ->set('code', 'FA2026')
        ->set('start_date', '2026-09-01')
        ->set('end_date', '2026-12-15')
        ->set('registration_start', '2026-08-01')
        ->set('registration_end', '2026-08-25')
        ->call('createTerm')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.terms.index'));

    $term = Term::where('code', 'FA2026')->first();
    expect($term)->not->toBeNull();
    expect($term->name)->toBe('Fall 2026');
});

test('create term validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.create')
        ->set('name', '')
        ->set('code', '')
        ->set('start_date', '')
        ->set('end_date', '')
        ->set('registration_start', '')
        ->set('registration_end', '')
        ->call('createTerm')
        ->assertHasErrors(['name', 'code', 'start_date', 'end_date', 'registration_start', 'registration_end']);
});

test('create term validates unique code', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['code' => 'FA2026']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.create')
        ->set('name', 'Fall 2026')
        ->set('code', 'FA2026')
        ->set('start_date', '2026-09-01')
        ->set('end_date', '2026-12-15')
        ->set('registration_start', '2026-08-01')
        ->set('registration_end', '2026-08-25')
        ->call('createTerm')
        ->assertHasErrors(['code']);
});

test('create term validates date ordering', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.create')
        ->set('name', 'Bad Term')
        ->set('code', 'BAD01')
        ->set('start_date', '2026-12-15')
        ->set('end_date', '2026-09-01')
        ->set('registration_start', '2026-08-01')
        ->set('registration_end', '2026-08-25')
        ->call('createTerm')
        ->assertHasErrors(['end_date']);
});

test('admin can view edit term page', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.terms.edit', $term))
        ->assertOk();
});

test('admin can update a term', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.edit', ['term' => $term])
        ->set('name', 'Updated Term')
        ->set('code', 'UPD01')
        ->call('updateTerm')
        ->assertHasNoErrors();

    $term->refresh();
    expect($term->name)->toBe('Updated Term');
    expect($term->code)->toBe('UPD01');
});

test('admin can toggle term active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.index')
        ->call('toggleActive', $term->id);

    $term->refresh();
    expect($term->is_active)->toBeFalse();
});

test('admin can delete a term', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.terms.edit', ['term' => $term])
        ->call('deleteTerm')
        ->assertRedirect(route('admin.terms.index'));

    expect(Term::find($term->id))->toBeNull();
});

test('student cannot access term management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.terms.index'))
        ->assertForbidden();
});

test('guest is redirected to login from term management', function () {
    $this->get(route('admin.terms.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access term management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.terms.index'))
        ->assertOk();
});

test('registrar can access term management', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.terms.index'))
        ->assertOk();
});
