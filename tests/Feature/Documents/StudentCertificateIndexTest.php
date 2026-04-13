<?php

use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('student can access their certificates page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.certificates.index'))
        ->assertOk();
});

test('student sees only their own certificates', function () {
    $student = User::factory()->asStudent()->create();
    $other = User::factory()->asStudent()->create();

    $ownCert = Certificate::factory()->forStudent($student)->create();
    $otherCert = Certificate::factory()->forStudent($other)->create();

    Livewire::actingAs($student)
        ->test('pages::registration.certificates.index')
        ->assertSee($ownCert->certificate_number)
        ->assertDontSee($otherCert->certificate_number);
});

test('active and revoked certificates are displayed', function () {
    $student = User::factory()->asStudent()->create();

    $active = Certificate::factory()->forStudent($student)->create();
    $revoked = Certificate::factory()->forStudent($student)->revoked()->create();

    Livewire::actingAs($student)
        ->test('pages::registration.certificates.index')
        ->assertSee($active->certificate_number)
        ->assertSee($revoked->certificate_number);
});

test('admin is forbidden from student certificates page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('registration.certificates.index'))
        ->assertForbidden();
});

test('instructor is forbidden from student certificates page', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('registration.certificates.index'))
        ->assertForbidden();
});

test('empty state shown when student has no certificates', function () {
    $student = User::factory()->asStudent()->create();

    Livewire::actingAs($student)
        ->test('pages::registration.certificates.index')
        ->assertSee('No certificates yet');
});
