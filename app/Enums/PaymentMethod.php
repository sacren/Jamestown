<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Check = 'check';
    case CreditCard = 'credit-card';
    case ACH = 'ach';
    case Refund = 'refund';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Check => 'Check',
            self::CreditCard => 'Credit Card',
            self::ACH => 'ACH Transfer',
            self::Refund => 'Refund',
            self::Other => 'Other',
        };
    }
}
