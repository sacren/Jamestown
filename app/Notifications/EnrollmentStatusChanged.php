<?php

namespace App\Notifications;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Notifications\Notification;

class EnrollmentStatusChanged extends Notification
{
    public function __construct(
        public Enrollment $enrollment,
        public EnrollmentStatus $newStatus,
    ) {
        $this->enrollment->loadMissing('section.course');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $courseCode = $this->enrollment->section->course->code;

        return [
            'title' => 'Enrollment Updated',
            'message' => "Your enrollment in {$courseCode} has been marked as ".ucfirst($this->newStatus->value).'.',
            'url' => route('registration.schedule'),
            'icon' => 'clipboard-document-list',
        ];
    }
}
