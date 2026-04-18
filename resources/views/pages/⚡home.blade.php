<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::marketing', [
    'description' => 'Hands-on trade training that builds careers — taught by working professionals at Empire Trade School.',
])] class extends Component {}; ?>

<div>
    <section class="relative isolate overflow-hidden bg-gradient-to-b from-white to-zinc-50 py-20 sm:py-28 lg:py-32 dark:from-zinc-900 dark:to-zinc-950">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl dark:text-white">
                {{ __('Skills that build empires.') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 sm:text-xl dark:text-zinc-300">
                {{ __(':brand equips you with the hands-on trade skills employers need — taught by working professionals.', ['brand' => config('app.name')]) }}
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <flux:button :href="route('dashboard')" variant="primary" wire:navigate>
                        {{ __('Go to dashboard') }}
                    </flux:button>
                @else
                    <flux:button :href="route('register')" variant="primary" wire:navigate>
                        {{ __('Get started') }}
                    </flux:button>
                    <flux:button :href="route('login')" variant="ghost" wire:navigate>
                        {{ __('Sign in') }}
                    </flux:button>
                @endauth
            </div>
        </div>
    </section>
</div>
