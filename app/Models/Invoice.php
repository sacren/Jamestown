<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enrollment_id', 'invoice_number', 'amount_due', 'issued_at', 'due_at', 'voided_at', 'notes'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function totalPaid(): string
    {
        $sum = $this->payments->sum(fn (Payment $payment) => (float) $payment->amount);

        return number_format($sum, 2, '.', '');
    }

    public function balance(): string
    {
        $balance = (float) $this->amount_due - (float) $this->totalPaid();

        return number_format($balance, 2, '.', '');
    }

    public function status(): InvoiceStatus
    {
        if ($this->voided_at !== null) {
            return InvoiceStatus::Voided;
        }

        $paid = (float) $this->totalPaid();
        $due = (float) $this->amount_due;

        if ($paid <= 0.0) {
            return InvoiceStatus::Unpaid;
        }

        if (abs($paid - $due) < 0.005) {
            return InvoiceStatus::Paid;
        }

        if ($paid < $due) {
            return InvoiceStatus::Partial;
        }

        return InvoiceStatus::Overpaid;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function scopeOutstanding(Builder $query): void
    {
        $query->whereNull('voided_at')
            ->whereRaw('amount_due <> COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0)');
    }
}
