<?php

namespace Database\Seeders;

use App\Actions\Billing\CreateInvoiceForEnrollment;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $action = app(CreateInvoiceForEnrollment::class);

        $recorders = User::role([Role::Admin->value, Role::Registrar->value, Role::SuperAdmin->value])->get();
        if ($recorders->isEmpty()) {
            return;
        }

        $enrollments = Enrollment::query()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('section.course', 'section.term')
            ->get();

        foreach ($enrollments as $enrollment) {
            $invoice = $action->handle($enrollment);
            if (! $invoice) {
                continue;
            }

            $recorder = $recorders->random();
            $roll = random_int(1, 100);

            if ($roll <= 60) {
                // Fully paid
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $recorder->id,
                    'amount' => $invoice->amount_due,
                    'method' => PaymentMethod::Cash,
                    'received_at' => $invoice->issued_at,
                ]);
            } elseif ($roll <= 80) {
                // Partial
                $percent = random_int(30, 70) / 100;
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $recorder->id,
                    'amount' => round((float) $invoice->amount_due * $percent, 2),
                    'method' => PaymentMethod::Check,
                    'received_at' => $invoice->issued_at,
                ]);
            } elseif ($roll <= 95) {
                // Unpaid — no payment
                continue;
            } else {
                // Refund scenario — full payment then partial refund
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $recorder->id,
                    'amount' => $invoice->amount_due,
                    'method' => PaymentMethod::CreditCard,
                    'received_at' => $invoice->issued_at,
                ]);
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $recorder->id,
                    'amount' => -round((float) $invoice->amount_due * 0.25, 2),
                    'method' => PaymentMethod::Refund,
                    'received_at' => now(),
                ]);
            }
        }
    }
}
