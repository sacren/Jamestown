<?php

namespace App\Actions\Billing;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Invoice;

class CreateInvoiceForEnrollment
{
    public function __construct(private GenerateInvoiceNumber $numberGenerator) {}

    public function handle(Enrollment $enrollment): ?Invoice
    {
        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            return null;
        }

        if ($enrollment->invoice()->exists()) {
            return $enrollment->invoice()->first();
        }

        $section = $enrollment->section()->with(['course', 'term'])->first();

        return Invoice::create([
            'enrollment_id' => $enrollment->id,
            'invoice_number' => $this->numberGenerator->handle(),
            'amount_due' => $section->course->tuition_amount,
            'issued_at' => now(),
            'due_at' => $section->term->start_date,
        ]);
    }
}
