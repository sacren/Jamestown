<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view billing index page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.invoices.index'))
        ->assertOk();
});

test('registrar can view billing index page', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.invoices.index'))
        ->assertOk();
});

test('super-admin can view billing index page', function () {
    $admin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.invoices.index'))
        ->assertOk();
});

test('instructor is forbidden from billing index page', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('admin.invoices.index'))
        ->assertForbidden();
});

test('student is forbidden from billing index page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.invoices.index'))
        ->assertForbidden();
});

test('search by invoice number filters results', function () {
    $admin = User::factory()->asAdmin()->create();
    Invoice::factory()->create(['invoice_number' => 'INV-2026-000001']);
    Invoice::factory()->create(['invoice_number' => 'INV-2026-000002']);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('search', '000001')
        ->assertSee('INV-2026-000001')
        ->assertDontSee('INV-2026-000002');
});

test('search by student name filters results', function () {
    $admin = User::factory()->asAdmin()->create();

    $alice = User::factory()->asStudent()->create(['name' => 'Alice Example']);
    $bob = User::factory()->asStudent()->create(['name' => 'Bob Sample']);

    $aliceInvoice = Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($alice)->create()
    )->create();

    Invoice::factory()->forEnrollment(
        Enrollment::factory()->forStudent($bob)->create()
    )->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('search', 'Alice')
        ->assertSee($aliceInvoice->invoice_number);
});

test('status filter for Unpaid returns only unpaid invoices', function () {
    $admin = User::factory()->asAdmin()->create();
    $unpaid = Invoice::factory()->create(['amount_due' => 500]);
    $paid = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($paid)->create(['amount' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('statusFilter', InvoiceStatus::Unpaid->value)
        ->assertSee($unpaid->invoice_number)
        ->assertDontSee($paid->invoice_number);
});

test('status filter for Paid returns only paid invoices', function () {
    $admin = User::factory()->asAdmin()->create();
    $unpaid = Invoice::factory()->create(['amount_due' => 500]);
    $paid = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($paid)->create(['amount' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('statusFilter', InvoiceStatus::Paid->value)
        ->assertSee($paid->invoice_number)
        ->assertDontSee($unpaid->invoice_number);
});

test('status filter for Partial returns partially paid invoices', function () {
    $admin = User::factory()->asAdmin()->create();
    $partial = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($partial)->create(['amount' => 100]);

    $unpaid = Invoice::factory()->create(['amount_due' => 500]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('statusFilter', InvoiceStatus::Partial->value)
        ->assertSee($partial->invoice_number)
        ->assertDontSee($unpaid->invoice_number);
});

test('status filter for Voided returns only voided invoices', function () {
    $admin = User::factory()->asAdmin()->create();
    $voided = Invoice::factory()->voided()->create();
    $active = Invoice::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('statusFilter', InvoiceStatus::Voided->value)
        ->assertSee($voided->invoice_number)
        ->assertDontSee($active->invoice_number);
});

test('term filter limits results to enrollments in that term', function () {
    $admin = User::factory()->asAdmin()->create();

    $termA = Term::factory()->create();
    $termB = Term::factory()->create();

    $sectionA = Section::factory()->create(['term_id' => $termA->id]);
    $sectionB = Section::factory()->create(['term_id' => $termB->id]);

    $invoiceA = Invoice::factory()->forEnrollment(Enrollment::factory()->forSection($sectionA)->create())->create();
    $invoiceB = Invoice::factory()->forEnrollment(Enrollment::factory()->forSection($sectionB)->create())->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->set('termFilter', (string) $termA->id)
        ->assertSee($invoiceA->invoice_number)
        ->assertDontSee($invoiceB->invoice_number);
});

test('stats strip shows outstanding, collected, and refunded totals', function () {
    $admin = User::factory()->asAdmin()->create();

    $partial = Invoice::factory()->create(['amount_due' => 1000]);
    Payment::factory()->forInvoice($partial)->create(['amount' => 400]);

    $paid = Invoice::factory()->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($paid)->create(['amount' => 500]);
    Payment::factory()->forInvoice($paid)->create([
        'amount' => -100,
        'method' => PaymentMethod::Refund,
    ]);

    $stats = Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->get('stats');

    // partial: $1000 due - $400 paid = $600 outstanding
    // refunded: $500 due - ($500 - $100) = $100 outstanding (now Partial after refund)
    expect($stats['outstanding'])->toBe(700.0);
    expect($stats['collected'])->toBe(900.0);
    expect($stats['refunded'])->toBe(100.0);
});

test('sort by amount_due toggles direction', function () {
    $admin = User::factory()->asAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.invoices.index')
        ->call('sortBy', 'amount_due')
        ->assertSet('sortField', 'amount_due')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'amount_due')
        ->assertSet('sortDirection', 'desc');
});
