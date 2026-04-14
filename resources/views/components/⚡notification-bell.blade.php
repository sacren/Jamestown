<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }
}; ?>

<div wire:poll.60s class="inline-flex">
    <a href="{{ route('notifications') }}" wire:navigate class="relative inline-flex items-center rounded-lg p-1.5 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
        <flux:icon.bell class="size-5" />
        @if ($this->unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </a>
</div>
