<?php

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view certificates index page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.index'))
        ->assertOk();
});

test('registrar can view certificates index page', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.certificates.index'))
        ->assertOk();
});

test('instructor is forbidden from certificates index', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.certificates.index'))
        ->assertForbidden();
});

test('student is forbidden from certificates index', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.certificates.index'))
        ->assertForbidden();
});

test('search by certificate number filters results', function () {
    $admin = User::factory()->asAdmin()->create();
    Certificate::factory()->create(['certificate_number' => 'CERT-2026-000001']);
    Certificate::factory()->create(['certificate_number' => 'CERT-2026-000002']);

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.index')
        ->set('search', '000001')
        ->assertSee('CERT-2026-000001')
        ->assertDontSee('CERT-2026-000002');
});

test('search by student name filters results', function () {
    $admin = User::factory()->asAdmin()->create();

    $alice = User::factory()->create(['name' => 'Alice Graduate']);
    $bob = User::factory()->create(['name' => 'Bob Student']);

    Certificate::factory()->forStudent($alice)->create();
    Certificate::factory()->forStudent($bob)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.index')
        ->set('search', 'Alice')
        ->assertSee('Alice Graduate')
        ->assertDontSee('Bob Student');
});

test('filter by active status shows only active certificates', function () {
    $admin = User::factory()->asAdmin()->create();
    $active = Certificate::factory()->create();
    $revoked = Certificate::factory()->revoked()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.index')
        ->set('statusFilter', CertificateStatus::Active->value)
        ->assertSee($active->certificate_number)
        ->assertDontSee($revoked->certificate_number);
});

test('filter by revoked status shows only revoked certificates', function () {
    $admin = User::factory()->asAdmin()->create();
    $active = Certificate::factory()->create();
    $revoked = Certificate::factory()->revoked()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.index')
        ->set('statusFilter', CertificateStatus::Revoked->value)
        ->assertSee($revoked->certificate_number)
        ->assertDontSee($active->certificate_number);
});

test('filter by program shows only certificates for that program', function () {
    $admin = User::factory()->asAdmin()->create();

    $programA = Program::factory()->create();
    $programB = Program::factory()->create();

    $certA = Certificate::factory()->forProgram($programA)->create();
    $certB = Certificate::factory()->forProgram($programB)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.index')
        ->set('programFilter', (string) $programA->id)
        ->assertSee($certA->certificate_number)
        ->assertDontSee($certB->certificate_number);
});

test('issue certificate link is present for users with manage permission', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.index'))
        ->assertSee('Issue Certificate');
});

test('pagination works', function () {
    $admin = User::factory()->asAdmin()->create();
    Certificate::factory()->count(20)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.index')
        ->assertSee('Next');
});
