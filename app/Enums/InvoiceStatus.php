<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overpaid = 'overpaid';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
            self::Overpaid => 'Overpaid',
            self::Voided => 'Voided',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'zinc',
            self::Partial => 'yellow',
            self::Paid => 'green',
            self::Overpaid => 'blue',
            self::Voided => 'red',
        };
    }
}
