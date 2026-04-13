<?php

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can mark enrolled enrollment as completed', function () {
    $admin = User::factory()->asAdmin()->create();
    $enrollment = Enrollment::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->call('completeEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Completed);
    expect($enrollment->completed_at)->not->toBeNull();
});

test('registrar can mark enrolled enrollment as completed', function () {
    $registrar = User::factory()->asRegistrar()->create();
    $enrollment = Enrollment::factory()->create();

    $this->actingAs($registrar);

    Livewire::test('pages::admin.enrollments.index')
        ->call('completeEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Completed);
});

test('cannot complete a dropped enrollment', function () {
    $admin = User::factory()->asAdmin()->create();
    $enrollment = Enrollment::factory()->dropped()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->call('completeEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Dropped);
});

test('cannot complete a withdrawn enrollment', function () {
    $admin = User::factory()->asAdmin()->create();
    $enrollment = Enrollment::factory()->withdrawn()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->call('completeEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Withdrawn);
});

test('cannot complete an already completed enrollment', function () {
    $admin = User::factory()->asAdmin()->create();
    $enrollment = Enrollment::factory()->completed()->create(['completed_at' => now()->subDay()]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.enrollments.index')
        ->call('completeEnrollment', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->completed_at->format('Y-m-d'))->toBe(now()->subDay()->format('Y-m-d'));
});
