<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewAnnouncement extends Notification
{
    public function __construct(
        public Announcement $announcement,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'message' => Str::limit($this->announcement->body, 100),
            'url' => route('dashboard'),
            'icon' => 'megaphone',
        ];
    }
}
