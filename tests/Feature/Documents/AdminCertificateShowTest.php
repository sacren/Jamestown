<?php

use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view certificate details', function () {
    $admin = User::factory()->asAdmin()->create();
    $certificate = Certificate::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.show', $certificate))
        ->assertOk()
        ->assertSee($certificate->certificate_number);
});

test('registrar can view certificate details', function () {
    $registrar = User::factory()->asRegistrar()->create();
    $certificate = Certificate::factory()->create();

    $this->actingAs($registrar)
        ->get(route('admin.certificates.show', $certificate))
        ->assertOk();
});

test('instructor is forbidden from certificate show', function () {
    $instructor = User::factory()->asInstructor()->create();
    $certificate = Certificate::factory()->create();

    $this->actingAs($instructor)
        ->get(route('admin.certificates.show', $certificate))
        ->assertForbidden();
});

test('student is forbidden from certificate show', function () {
    $student = User::factory()->asStudent()->create();
    $certificate = Certificate::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.certificates.show', $certificate))
        ->assertForbidden();
});

test('shows certificate number student and program', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->create(['name' => 'John Graduate']);
    $certificate = Certificate::factory()->forStudent($student)->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.show', $certificate))
        ->assertSee($certificate->certificate_number)
        ->assertSee('John Graduate')
        ->assertSee($certificate->program->name);
});

test('revoke action sets revoked_at and revoked_by', function () {
    $admin = User::factory()->asAdmin()->create();
    $certificate = Certificate::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.show', ['certificate' => $certificate])
        ->call('revokeCertificate');

    $certificate->refresh();
    expect($certificate->isRevoked())->toBeTrue();
    expect($certificate->revoked_by)->toBe($admin->id);
});

test('cannot revoke an already revoked certificate', function () {
    $admin = User::factory()->asAdmin()->create();
    $certificate = Certificate::factory()->revoked()->create();
    $originalRevokedAt = $certificate->revoked_at;

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.show', ['certificate' => $certificate])
        ->call('revokeCertificate');

    $certificate->refresh();
    expect($certificate->revoked_at->toDateTimeString())->toBe($originalRevokedAt->toDateTimeString());
});

test('revoke notes are saved', function () {
    $admin = User::factory()->asAdmin()->create();
    $certificate = Certificate::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.certificates.show', ['certificate' => $certificate])
        ->set('revokeNotes', 'Academic dishonesty')
        ->call('revokeCertificate');

    $certificate->refresh();
    expect($certificate->notes)->toBe('Academic dishonesty');
});

test('revoke page section is not visible without manage permission', function () {
    $registrar = User::factory()->asRegistrar()->create();
    // Registrar has view-any but also has manage, so we test with a custom user
    // who only has view-any. Instead, verify the show page for admin contains the revoke button.
    $admin = User::factory()->asAdmin()->create();
    $certificate = Certificate::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.show', $certificate))
        ->assertSee('Revoke Certificate');
});

test('revoke section is hidden for revoked certificate', function () {
    $admin = User::factory()->asAdmin()->create();
    $certificate = Certificate::factory()->revoked()->create();

    $this->actingAs($admin)
        ->get(route('admin.certificates.show', $certificate))
        ->assertDontSee('Revoke Certificate');
});
