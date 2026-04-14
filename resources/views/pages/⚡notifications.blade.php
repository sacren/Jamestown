<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Notifications')] class extends Component {
    use WithPagination;

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;
        if ($url) {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Notifications') }}</flux:heading>
            <flux:subheading>{{ __('Your recent notifications') }}</flux:subheading>
        </div>
        @if ($this->unreadCount > 0)
            <flux:button variant="ghost" wire:click="markAllAsRead">
                {{ __('Mark all as read') }}
            </flux:button>
        @endif
    </div>

    @if ($this->notifications->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle">{{ __('No notifications yet') }}</flux:text>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($this->notifications as $notification)
                <div
                    wire:key="{{ $notification->id }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="cursor-pointer rounded-lg border p-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $notification->read_at ? 'border-zinc-200 dark:border-zinc-700' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' }}"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $notification->data['title'] ?? __('Notification') }}
                                </span>
                                @if (! $notification->read_at)
                                    <span class="inline-block size-2 rounded-full bg-blue-500"></span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $this->notifications->links() }}
        </div>
    @endif
</section>
