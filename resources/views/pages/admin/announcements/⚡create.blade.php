<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncement;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Announcement')] class extends Component {
    public string $title = '';
    public string $body = '';
    public string $audience = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage-announcements'), 403);
    }

    public function save(bool $publish = false): void
    {
        $this->validate([
            'title' => ['required', 'max:255'],
            'body' => ['required'],
            'audience' => ['required', 'in:all,students,instructors'],
        ]);

        $announcement = Announcement::create([
            'title' => $this->title,
            'body' => $this->body,
            'audience' => $this->audience,
            'published_at' => $publish ? now() : null,
            'author_id' => auth()->id(),
        ]);

        if ($publish) {
            $this->dispatchAnnouncementNotifications($announcement);
        }

        session()->flash('status', $publish ? __('Announcement published.') : __('Announcement saved as draft.'));

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
        <flux:heading size="xl">{{ __('New Announcement') }}</flux:heading>
        <flux:subheading>{{ __('Create a new announcement for students and instructors') }}</flux:subheading>
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
            <flux:button variant="primary" wire:click="save(true)">
                {{ __('Publish Now') }}
            </flux:button>
            <flux:button variant="ghost" wire:click="save(false)">
                {{ __('Save as Draft') }}
            </flux:button>
        </div>
    </form>
</section>
