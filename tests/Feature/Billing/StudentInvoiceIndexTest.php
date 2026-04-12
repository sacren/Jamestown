<?php

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('student can view my billing page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('registration.invoices.index'))
        ->assertOk();
});

test('student sees only their own invoices', function () {
    $student = User::factory()->asStudent()->create();
    $other = User::factory()->asStudent()->create();

    $ownInvoice = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create();

    $otherInvoice = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($other)->create()
    )->create();

    Livewire::actingAs($student)
        ->test('pages::registration.invoices.index')
        ->assertSee($ownInvoice->invoice_number)
        ->assertDontSee($otherInvoice->invoice_number);
});

test('total balance banner shows correct sum', function () {
    $student = User::factory()->asStudent()->create();

    $a = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($a)->create(['amount' => 200]);

    $b = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create(['amount_due' => 700]);

    $balance = Livewire::actingAs($student)
        ->test('pages::registration.invoices.index')
        ->get('totalBalance');

    expect($balance)->toBe(1000.0);
});

test('voided invoices are excluded from total balance', function () {
    $student = User::factory()->asStudent()->create();

    $voided = Invoice::factory()->voided()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create(['amount_due' => 500]);

    $active = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create(['amount_due' => 300]);

    $balance = Livewire::actingAs($student)
        ->test('pages::registration.invoices.index')
        ->get('totalBalance');

    expect($balance)->toBe(300.0);
});

test('instructor cannot access my billing page', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('registration.invoices.index'))
        ->assertForbidden();
});

test('admin cannot access my billing page (student-only)', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('registration.invoices.index'))
        ->assertForbidden();
});

test('empty state renders when student has no invoices', function () {
    $student = User::factory()->asStudent()->create();

    Livewire::actingAs($student)
        ->test('pages::registration.invoices.index')
        ->assertSee('No invoices yet');
});
