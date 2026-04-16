<?php

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('student can view their own invoice', function () {
    $student = User::factory()->asStudent()->create();
    $invoice = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create();

    $this->actingAs($student)
        ->get(route('registration.invoices.show', $invoice))
        ->assertOk()
        ->assertSee($invoice->invoice_number);
});

test('student is forbidden from viewing another students invoice', function () {
    $student = User::factory()->asStudent()->create();
    $other = User::factory()->asStudent()->create();
    $invoice = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($other)->create()
    )->create();

    $this->actingAs($student)
        ->get(route('registration.invoices.show', $invoice))
        ->assertForbidden();
});

test('student show page has no payment form', function () {
    $student = User::factory()->asStudent()->create();
    $invoice = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($student)->create()
    )->create();

    $this->actingAs($student)
        ->get(route('registration.invoices.show', $invoice))
        ->assertOk()
        ->assertDontSee('Record Payment')
        ->assertDontSee('Void Invoice');
});

test('student show page does not render payment delete actions', function () {
    $student = User::factory()->asStudent()->create();
    $enrollment = Enrollment::factory()->forStudent($student)->create();
    $invoice = Invoice::factory()->forEnrollment($enrollment)->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 200]);

    $response = $this->actingAs($student)
        ->get(route('registration.invoices.show', $invoice))
        ->assertOk();

    $response->assertDontSee('deletePayment');
});
