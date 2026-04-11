<?php

namespace App\Actions\Billing;

use App\Models\Invoice;

class GenerateInvoiceNumber
{
    public function handle(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";

        $last = Invoice::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSeq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }
}
