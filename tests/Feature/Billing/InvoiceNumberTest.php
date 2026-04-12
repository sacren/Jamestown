<?php

use App\Actions\Billing\GenerateInvoiceNumber;
use App\Models\Invoice;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('generator produces INV-YYYY-NNNNNN format', function () {
    $number = app(GenerateInvoiceNumber::class)->handle();

    expect($number)->toMatch('/^INV-\d{4}-\d{6}$/');
});

test('sequence starts at 000001 for a fresh year', function () {
    $number = app(GenerateInvoiceNumber::class)->handle();

    expect($number)->toBe('INV-'.now()->year.'-000001');
});

test('sequence increments across calls', function () {
    $generator = app(GenerateInvoiceNumber::class);

    $first = $generator->handle();
    Invoice::factory()->create(['invoice_number' => $first]);

    $second = $generator->handle();
    Invoice::factory()->create(['invoice_number' => $second]);

    $third = $generator->handle();

    expect($first)->toBe('INV-'.now()->year.'-000001');
    expect($second)->toBe('INV-'.now()->year.'-000002');
    expect($third)->toBe('INV-'.now()->year.'-000003');
});

test('sequence resets per year', function () {
    Carbon::setTestNow(Carbon::create(2025, 6, 1));
    $prevYear = app(GenerateInvoiceNumber::class)->handle();
    Invoice::factory()->create(['invoice_number' => $prevYear]);

    Carbon::setTestNow(Carbon::create(2026, 1, 15));
    $newYear = app(GenerateInvoiceNumber::class)->handle();

    expect($prevYear)->toBe('INV-2025-000001');
    expect($newYear)->toBe('INV-2026-000001');

    Carbon::setTestNow();
});

test('numbers are unique across sequential factory creation', function () {
    $numbers = collect(range(1, 5))->map(
        fn () => Invoice::factory()->create()->invoice_number
    );

    expect($numbers->unique()->count())->toBe(5);
});
