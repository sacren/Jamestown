<?php

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view any invoice', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.invoices.show', $invoice))
        ->assertOk();
});

test('registrar can view any invoice', function () {
    $registrar = User::factory()->asRegistrar()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($registrar)
        ->get(route('admin.invoices.show', $invoice))
        ->assertOk();
});

test('instructor is forbidden from admin invoice show', function () {
    $instructor = User::factory()->asInstructor()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($instructor)
        ->get(route('admin.invoices.show', $invoice))
        ->assertForbidden();
});

test('student is forbidden from admin invoice show', function () {
    $student = User::factory()->asStudent()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.invoices.show', $invoice))
        ->assertForbidden();
});

test('recording a cash payment updates balance and status', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '500')
        ->set('method', PaymentMethod::Cash->value)
        ->set('received_at', now()->format('Y-m-d\TH:i'))
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect((float) $invoice->fresh()->balance())->toBe(0.0);
    expect($invoice->fresh()->status()->value)->toBe('paid');
});

test('recording a refund with negative amount reduces total paid', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '-100')
        ->set('method', PaymentMethod::Refund->value)
        ->set('received_at', now()->format('Y-m-d\TH:i'))
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect((float) $invoice->fresh()->totalPaid())->toBe(400.0);
});

test('rejects refund with positive amount', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '100')
        ->set('method', PaymentMethod::Refund->value)
        ->set('received_at', now()->format('Y-m-d\TH:i'))
        ->call('recordPayment')
        ->assertHasErrors('amount');

    expect($invoice->fresh()->payments)->toHaveCount(0);
});

test('rejects non-refund payment with negative amount', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '-50')
        ->set('method', PaymentMethod::Cash->value)
        ->set('received_at', now()->format('Y-m-d\TH:i'))
        ->call('recordPayment')
        ->assertHasErrors('amount');
});

test('rejects zero amount payment', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '0')
        ->set('method', PaymentMethod::Cash->value)
        ->set('received_at', now()->format('Y-m-d\TH:i'))
        ->call('recordPayment')
        ->assertHasErrors('amount');
});

test('rejects received_at in the future', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '100')
        ->set('method', PaymentMethod::Cash->value)
        ->set('received_at', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('recordPayment')
        ->assertHasErrors('received_at');
});

test('admin can delete a payment and balance recalculates', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    $payment = Payment::factory()->forInvoice($invoice)->create(['amount' => 200]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->call('deletePayment', $payment->id);

    expect(Payment::find($payment->id))->toBeNull();
    expect((float) $invoice->fresh()->balance())->toBe(500.0);
});

test('registrar cannot delete payments', function () {
    $registrar = User::factory()->asRegistrar()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    $payment = Payment::factory()->forInvoice($invoice)->create(['amount' => 200]);

    Livewire::actingAs($registrar)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->call('deletePayment', $payment->id)
        ->assertStatus(403);
});

test('recorded_by captures current user on payment creation', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->set('amount', '100')
        ->set('method', PaymentMethod::Cash->value)
        ->set('received_at', now()->format('Y-m-d\TH:i'))
        ->call('recordPayment');

    expect($invoice->fresh()->payments()->first()->recorded_by)->toBe($admin->id);
});

test('can void invoice with no payments', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->call('voidInvoice');

    expect($invoice->fresh()->isVoided())->toBeTrue();
});

test('void action is unavailable when payments exist', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->create();
    Payment::factory()->forInvoice($invoice)->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice]);

    expect($component->get('canVoid'))->toBeFalse();

    $component->call('voidInvoice')->assertHasErrors('void');
    expect($invoice->fresh()->isVoided())->toBeFalse();
});

test('cannot re-void an already voided invoice', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->voided()->create();
    $voidedAt = $invoice->voided_at;

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.show', ['invoice' => $invoice])
        ->call('voidInvoice');

    expect($invoice->fresh()->voided_at->toDateTimeString())
        ->toBe($voidedAt->toDateTimeString());
});
