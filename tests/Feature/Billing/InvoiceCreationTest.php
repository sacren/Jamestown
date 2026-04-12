<?php

use App\Actions\Billing\CreateInvoiceForEnrollment;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Section;
use App\Models\Term;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('invoice is created for an enrolled enrollment', function () {
    $course = Course::factory()->create(['tuition_amount' => 750]);
    $term = Term::factory()->create(['start_date' => '2026-09-01']);
    $section = Section::factory()->create(['course_id' => $course->id, 'term_id' => $term->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();

    $invoice = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect((float) $invoice->amount_due)->toBe(750.0);
    expect($invoice->due_at->toDateString())->toBe('2026-09-01');
});

test('invoice number follows INV-YYYY-NNNNNN format', function () {
    $enrollment = Enrollment::factory()->create();

    $invoice = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{6}$/');
});

test('no invoice is created for a dropped enrollment', function () {
    $enrollment = Enrollment::factory()->dropped()->create();

    $invoice = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect($invoice)->toBeNull();
    expect(Invoice::count())->toBe(0);
});

test('no invoice is created for a withdrawn enrollment', function () {
    $enrollment = Enrollment::factory()->withdrawn()->create();

    $invoice = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect($invoice)->toBeNull();
});

test('creation action is idempotent', function () {
    $enrollment = Enrollment::factory()->create();

    $first = app(CreateInvoiceForEnrollment::class)->handle($enrollment);
    $second = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect($first->id)->toBe($second->id);
    expect(Invoice::count())->toBe(1);
});

test('enrollment has one invoice relationship', function () {
    $enrollment = Enrollment::factory()->create();
    $invoice = Invoice::factory()->forEnrollment($enrollment)->create();

    expect($enrollment->fresh()->invoice)->not->toBeNull();
    expect($enrollment->fresh()->invoice->id)->toBe($invoice->id);
});

test('amount_due matches course tuition_amount', function () {
    $course = Course::factory()->create(['tuition_amount' => 900]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();

    $invoice = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect((float) $invoice->amount_due)->toBe(900.0);
});

test('due_at matches term start date', function () {
    $term = Term::factory()->create(['start_date' => '2027-01-15']);
    $section = Section::factory()->create(['term_id' => $term->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();

    $invoice = app(CreateInvoiceForEnrollment::class)->handle($enrollment);

    expect($invoice->due_at->toDateString())->toBe('2027-01-15');
});
