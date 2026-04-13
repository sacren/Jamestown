<?php

use App\Actions\Documents\GenerateCertificateNumber;
use App\Models\Certificate;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('certificate number matches CERT-YYYY-NNNNNN format', function () {
    $number = app(GenerateCertificateNumber::class)->handle();

    expect($number)->toMatch('/^CERT-\d{4}-\d{6}$/');
});

test('first certificate number starts at 000001', function () {
    $number = app(GenerateCertificateNumber::class)->handle();

    expect($number)->toBe('CERT-'.now()->year.'-000001');
});

test('certificate numbers increment sequentially', function () {
    $generator = app(GenerateCertificateNumber::class);
    $year = now()->year;

    Certificate::factory()->create(['certificate_number' => "CERT-{$year}-000001"]);

    $next = $generator->handle();

    expect($next)->toBe("CERT-{$year}-000002");
});

test('certificate number resets per year', function () {
    Certificate::factory()->create(['certificate_number' => 'CERT-2025-000005']);

    $number = app(GenerateCertificateNumber::class)->handle();

    expect($number)->toBe('CERT-'.now()->year.'-000001');
});
