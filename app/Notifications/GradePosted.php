<?php

namespace App\Notifications;

use App\Models\Assessment;
use App\Models\Enrollment;
use Illuminate\Notifications\Notification;

class GradePosted extends Notification
{
    public function __construct(
        public Enrollment $enrollment,
        public Assessment $assessment,
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
            'title' => 'Grade Posted',
            'message' => "A grade was posted for {$this->assessment->title} in {$courseCode}.",
            'url' => route('registration.grades'),
            'icon' => 'chart-bar',
        ];
    }
}
