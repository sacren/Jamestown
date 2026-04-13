<?php

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('certificate belongs to a student', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->student)->toBeInstanceOf(User::class);
});

test('certificate belongs to a program', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->program)->toBeInstanceOf(Program::class);
});

test('certificate belongs to an issuer', function () {
    $issuer = User::factory()->create();
    $certificate = Certificate::factory()->create(['issued_by' => $issuer->id]);

    expect($certificate->issuer->id)->toBe($issuer->id);
});

test('certificate belongs to a revoker when revoked', function () {
    $revoker = User::factory()->create();
    $certificate = Certificate::factory()->revoked()->create(['revoked_by' => $revoker->id]);

    expect($certificate->revoker->id)->toBe($revoker->id);
});

test('status returns Active when revoked_at is null', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->status())->toBe(CertificateStatus::Active);
});

test('status returns Revoked when revoked_at is set', function () {
    $certificate = Certificate::factory()->revoked()->create();

    expect($certificate->status())->toBe(CertificateStatus::Revoked);
});

test('isRevoked returns true when revoked_at is present', function () {
    $certificate = Certificate::factory()->revoked()->create();

    expect($certificate->isRevoked())->toBeTrue();
});

test('isRevoked returns false when revoked_at is null', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->isRevoked())->toBeFalse();
});

test('factory creates a valid certificate', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->id)->toBeGreaterThan(0);
    expect($certificate->certificate_number)->toStartWith('CERT-');
    expect($certificate->issued_at)->not->toBeNull();
});

test('unique constraint prevents duplicate certificate for same student and program', function () {
    $student = User::factory()->create();
    $program = Program::factory()->create();

    Certificate::factory()->forStudent($student)->forProgram($program)->create();

    Certificate::factory()->forStudent($student)->forProgram($program)->create();
})->throws(QueryException::class);

test('user has many certificates', function () {
    $user = User::factory()->create();
    Certificate::factory()->forStudent($user)->count(2)->create();

    expect($user->certificates)->toHaveCount(2);
});

test('program has many certificates', function () {
    $program = Program::factory()->create();
    Certificate::factory()->forProgram($program)->count(2)->create();

    expect($program->certificates)->toHaveCount(2);
});
