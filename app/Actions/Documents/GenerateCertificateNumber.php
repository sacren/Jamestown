<?php

namespace App\Actions\Documents;

use App\Models\Certificate;

class GenerateCertificateNumber
{
    public function handle(): string
    {
        $year = now()->year;
        $prefix = "CERT-{$year}-";

        $last = Certificate::query()
            ->where('certificate_number', 'like', "{$prefix}%")
            ->orderByDesc('certificate_number')
            ->value('certificate_number');

        $nextSeq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }
}
