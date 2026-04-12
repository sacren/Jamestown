<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invoice_id' => InvoiceFactory::new(),
            'recorded_by' => UserFactory::new(),
            'amount' => 100.00,
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'received_at' => now(),
            'notes' => null,
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn () => ['invoice_id' => $invoice->id]);
    }

    public function fullPayment(): static
    {
        return $this->state(function (array $attributes) {
            $invoiceId = $attributes['invoice_id'] instanceof \Closure || is_object($attributes['invoice_id'])
                ? null
                : $attributes['invoice_id'];

            $amount = $invoiceId
                ? (float) Invoice::find($invoiceId)?->amount_due
                : 500.00;

            return ['amount' => $amount];
        });
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Refund,
            'amount' => -1 * abs((float) ($attributes['amount'] ?? 100.00)),
        ]);
    }
}
