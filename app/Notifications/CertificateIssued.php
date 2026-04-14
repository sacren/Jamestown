<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Notifications\Notification;

class CertificateIssued extends Notification
{
    public function __construct(
        public Certificate $certificate,
    ) {
        $this->certificate->loadMissing('program');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Certificate Issued',
            'message' => "Your certificate for {$this->certificate->program->name} has been issued ({$this->certificate->certificate_number}).",
            'url' => route('registration.certificates.index'),
            'icon' => 'document-check',
        ];
    }
}
