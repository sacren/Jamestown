<?php

use App\Enums\PaymentMethod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can access financial report', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.financial'))
        ->assertOk();
});

test('student cannot access financial report', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.reports.financial'))
        ->assertForbidden();
});

test('revenue totals calculated correctly', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();
    $invoice = Invoice::factory()->forEnrollment($enrollment)->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 500, 'method' => PaymentMethod::Cash]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial');

    $stats = $component->get('revenueStats');
    expect($stats['total_invoiced'])->toBe(500.0);
    expect($stats['net_revenue'])->toBe(500.0);
    expect($stats['outstanding'])->toBe(0.0);
    expect($stats['collection_rate'])->toBe(100.0);
});

test('outstanding balance correct', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();
    $invoice = Invoice::factory()->forEnrollment($enrollment)->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 200, 'method' => PaymentMethod::Check]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial');

    $stats = $component->get('revenueStats');
    expect($stats['outstanding'])->toBe(300.0);
    expect($stats['collection_rate'])->toBe(40.0);
});

test('voided invoices excluded from all totals', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    $enrollment1 = Enrollment::factory()->forSection($section)->create();
    $enrollment2 = Enrollment::factory()->forSection($section)->create();

    Invoice::factory()->forEnrollment($enrollment1)->create(['amount_due' => 500]);
    Invoice::factory()->forEnrollment($enrollment2)->voided()->create(['amount_due' => 1000]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial');

    // Only the non-voided invoice should count
    expect($component->get('revenueStats')['total_invoiced'])->toBe(500.0);
});

test('refund payments reduce net revenue', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $enrollment = Enrollment::factory()->forSection($section)->create();
    $invoice = Invoice::factory()->forEnrollment($enrollment)->create(['amount_due' => 500]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => 500, 'method' => PaymentMethod::CreditCard]);
    Payment::factory()->forInvoice($invoice)->create(['amount' => -100, 'method' => PaymentMethod::Refund]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial');

    $stats = $component->get('revenueStats');
    expect($stats['net_revenue'])->toBe(400.0);
    expect($stats['outstanding'])->toBe(100.0);
});

test('payment method breakdown shows correct counts', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);

    $enrollment1 = Enrollment::factory()->forSection($section)->create();
    $enrollment2 = Enrollment::factory()->forSection($section)->create();

    $invoice1 = Invoice::factory()->forEnrollment($enrollment1)->create(['amount_due' => 500]);
    $invoice2 = Invoice::factory()->forEnrollment($enrollment2)->create(['amount_due' => 300]);

    Payment::factory()->forInvoice($invoice1)->create(['amount' => 500, 'method' => PaymentMethod::Cash]);
    Payment::factory()->forInvoice($invoice2)->create(['amount' => 300, 'method' => PaymentMethod::Cash]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial');

    $breakdown = $component->get('paymentMethodBreakdown');
    $cashRow = $breakdown->rows->first(fn ($r) => $r->method === PaymentMethod::Cash);
    expect($cashRow->count)->toBe(2);
    expect($cashRow->total)->toBe(800.0);
});

test('outstanding invoices listed correctly', function () {
    $admin = User::factory()->asAdmin()->create();
    $student = User::factory()->asStudent()->create(['name' => 'Owing Oscar']);
    $term = Term::factory()->create(['is_active' => true]);
    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section = Section::factory()->forCourse($course)->create(['term_id' => $term->id]);
    $enrollment = Enrollment::factory()->forStudent($student)->forSection($section)->create();
    Invoice::factory()->forEnrollment($enrollment)->create(['amount_due' => 600]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial')
        ->assertSee('Owing Oscar')
        ->assertSee('Outstanding Invoices');
});

test('term filter changes results', function () {
    $admin = User::factory()->asAdmin()->create();
    $term1 = Term::factory()->create(['is_active' => true]);
    $term2 = Term::factory()->create(['is_active' => false]);

    $program = Program::factory()->create();
    $course = Course::factory()->for($program)->create();
    $section1 = Section::factory()->forCourse($course)->create(['term_id' => $term1->id]);
    $section2 = Section::factory()->forCourse($course)->create(['term_id' => $term2->id, 'section_number' => '02']);

    $enrollment1 = Enrollment::factory()->forSection($section1)->create();
    $enrollment2 = Enrollment::factory()->forSection($section2)->create();

    Invoice::factory()->forEnrollment($enrollment1)->create(['amount_due' => 500]);
    Invoice::factory()->forEnrollment($enrollment2)->create(['amount_due' => 900]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial');

    // Default active term → $500
    expect($component->get('revenueStats')['total_invoiced'])->toBe(500.0);

    // Switch to term2 → $900
    $component->set('termId', (string) $term2->id);
    expect($component->get('revenueStats')['total_invoiced'])->toBe(900.0);
});

test('handles no invoices gracefully', function () {
    $admin = User::factory()->asAdmin()->create();
    Term::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports.financial')
        ->assertSee('No invoices found');
});
