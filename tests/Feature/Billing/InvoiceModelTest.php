<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('invoice belongs to an enrollment', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice->enrollment)->not->toBeNull();
});

test('invoice has many payments', function () {
    $invoice = Invoice::factory()->create();
    Payment::factory()->count(3)->forInvoice($invoice)->create();

    expect($invoice->payments)->toHaveCount(3);
});

test('totalPaid sums positive payments', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Payment::factory()->forInvoice($invoice)->create(['amount' => 100]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 200]);

    expect((float) $invoice->fresh()->totalPaid())->toBe(300.0);
});

test('totalPaid accounts for negative refund payments', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    Payment::factory()->forInvoice($invoice)->create(['amount' => 500]);
    Payment::factory()->forInvoice($invoice)->create([
        'amount' => -100,
        'method' => PaymentMethod::Refund,
    ]);

    expect((float) $invoice->fresh()->totalPaid())->toBe(400.0);
});

test('balance is amount due minus total paid', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 150]);

    expect((float) $invoice->fresh()->balance())->toBe(350.0);
});

test('status returns Unpaid when no payments exist', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);

    expect($invoice->fresh()->status())->toBe(InvoiceStatus::Unpaid);
});

test('status returns Partial when paid is less than due', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 200]);

    expect($invoice->fresh()->status())->toBe(InvoiceStatus::Partial);
});

test('status returns Paid when paid equals due', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 500]);

    expect($invoice->fresh()->status())->toBe(InvoiceStatus::Paid);
});

test('status returns Overpaid when paid exceeds due', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 600]);

    expect($invoice->fresh()->status())->toBe(InvoiceStatus::Overpaid);
});

test('status returns Voided when voided_at is set regardless of payments', function () {
    $invoice = Invoice::factory()->voided()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 500]);

    expect($invoice->fresh()->status())->toBe(InvoiceStatus::Voided);
});

test('isVoided returns true when voided_at is present', function () {
    $invoice = Invoice::factory()->voided()->create();

    expect($invoice->isVoided())->toBeTrue();
});

test('isVoided returns false when voided_at is null', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice->isVoided())->toBeFalse();
});

test('scopeOutstanding excludes paid invoices', function () {
    $paid = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($paid)->create(['amount' => 500]);

    $unpaid = Invoice::factory()->create(['amount_due' => 500]);

    $outstanding = Invoice::query()->outstanding()->pluck('id');

    expect($outstanding)->toContain($unpaid->id);
    expect($outstanding)->not->toContain($paid->id);
});

test('scopeOutstanding excludes voided invoices', function () {
    $voided = Invoice::factory()->voided()->create(['amount_due' => 500]);
    $unpaid = Invoice::factory()->create(['amount_due' => 500]);

    $outstanding = Invoice::query()->outstanding()->pluck('id');

    expect($outstanding)->toContain($unpaid->id);
    expect($outstanding)->not->toContain($voided->id);
});

test('scopeOutstanding includes partial invoices', function () {
    $invoice = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 200]);

    expect(Invoice::query()->outstanding()->pluck('id'))->toContain($invoice->id);
});

test('payment method is cast to enum', function () {
    $payment = Payment::factory()->create(['method' => PaymentMethod::Check]);

    expect($payment->method)->toBe(PaymentMethod::Check);
});

test('payment belongs to recorder user', function () {
    $payment = Payment::factory()->create();

    expect($payment->recorder)->not->toBeNull();
});
