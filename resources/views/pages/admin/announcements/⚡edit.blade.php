<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncement;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Announcement')] class extends Component {
    public Announcement $announcement;
    public string $title = '';
    public string $body = '';
    public string $audience = 'all';

    public function mount(Announcement $announcement): void
    {
        abort_unless(auth()->user()->can('manage-announcements'), 403);

        $this->announcement = $announcement;
        $this->title = $announcement->title;
        $this->body = $announcement->body;
        $this->audience = $announcement->audience->value;
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'max:255'],
            'body' => ['required'],
            'audience' => ['required', 'in:all,students,instructors'],
        ]);

        $this->announcement->update([
            'title' => $this->title,
            'body' => $this->body,
            'audience' => $this->audience,
        ]);

        session()->flash('status', __('Announcement updated.'));

        $this->redirect(route('admin.announcements.index'), navigate: true);
    }

    public function publish(): void
    {
        $this->validate([
            'title' => ['required', 'max:255'],
            'body' => ['required'],
            'audience' => ['required', 'in:all,students,instructors'],
        ]);

        $shouldNotify = $this->announcement->notified_at === null;

        $this->announcement->update([
            'title' => $this->title,
            'body' => $this->body,
            'audience' => $this->audience,
            'published_at' => now(),
        ]);

        if ($shouldNotify) {
            $this->dispatchAnnouncementNotifications($this->announcement);
        }

        session()->flash('status', __('Announcement published.'));

        $this->redirect(route('admin.announcements.index'), navigate: true);
    }

    public function unpublish(): void
    {
        $this->announcement->update(['published_at' => null]);

        session()->flash('status', __('Announcement unpublished.'));

        $this->redirect(route('admin.announcements.index'), navigate: true);
    }

    private function dispatchAnnouncementNotifications(Announcement $announcement): void
    {
        $audience = AnnouncementAudience::from($announcement->audience->value);

        $users = match ($audience) {
            AnnouncementAudience::All => User::role(['student', 'instructor'])->get(),
            AnnouncementAudience::Students => User::role('student')->get(),
            AnnouncementAudience::Instructors => User::role('instructor')->get(),
        };

        Notification::send($users, new NewAnnouncement($announcement));
        $announcement->update(['notified_at' => now()]);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.announcements.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Announcements') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit Announcement') }}</flux:heading>
    </div>

    <form class="space-y-6">
        <flux:input wire:model="title" :label="__('Title')" placeholder="{{ __('Announcement title') }}" />

        <flux:textarea wire:model="body" :label="__('Body')" rows="6" placeholder="{{ __('Write your announcement...') }}" />

        <flux:select wire:model="audience" :label="__('Audience')">
            @foreach (AnnouncementAudience::cases() as $option)
                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-3">
            @if ($announcement->isPublished())
                <flux:button variant="primary" wire:click="save">
                    {{ __('Update') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="unpublish" wire:confirm="{{ __('Are you sure you want to unpublish this announcement?') }}">
                    {{ __('Unpublish') }}
                </flux:button>
            @else
                <flux:button variant="primary" wire:click="publish">
                    {{ __('Publish Now') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="save">
                    {{ __('Save as Draft') }}
                </flux:button>
            @endif
        </div>
    </form>
</section>
