<?php

namespace Database\Factories;

use App\Actions\Billing\GenerateInvoiceNumber;
use App\Models\Enrollment;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'enrollment_id' => EnrollmentFactory::new(),
            'invoice_number' => fn () => app(GenerateInvoiceNumber::class)->handle(),
            'amount_due' => fake()->randomElement([450, 500, 600, 750, 900]),
            'issued_at' => now(),
            'due_at' => now()->addDays(30)->toDateString(),
        ];
    }

    public function forEnrollment(Enrollment $enrollment): static
    {
        return $this->state(fn () => ['enrollment_id' => $enrollment->id]);
    }

    public function voided(): static
    {
        return $this->state(fn () => ['voided_at' => now()]);
    }
}
