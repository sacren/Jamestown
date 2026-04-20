<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::marketing', [
    'description' => 'Learn about Empire Trade School — our mission to equip students with hands-on trade skills taught by working professionals.',
])] class extends Component {
}; ?>

<div>
    <section class="bg-gradient-to-b from-white to-zinc-50 py-20 sm:py-28 dark:from-zinc-900 dark:to-zinc-950">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('About') }}
            </p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                {{ __('Skills that build empires.') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-300">
                {{ __(':brand was founded on a simple idea: people learn trades best from people who work them every day.', ['brand' => config('app.name')]) }}
            </p>
        </div>
    </section>

    <x-marketing.section
        :eyebrow="__('Our mission')"
        :heading="__('Hands-on training, real careers')"
        :description="__('We prepare students for skilled trade careers with practical instruction, industry-standard tools, and a path from enrollment to credential.')"
    >
        <div class="grid gap-6 sm:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:icon name="wrench-screwdriver" class="size-8 text-zinc-900 dark:text-white" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Practical instruction') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Every program pairs classroom fundamentals with shop-floor work on the tools you will use on the job.') }}
                </p>
            </div>
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:icon name="user-group" class="size-8 text-zinc-900 dark:text-white" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Working professionals') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Instructors are active tradespeople who know what employers expect on day one.') }}
                </p>
            </div>
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:icon name="academic-cap" class="size-8 text-zinc-900 dark:text-white" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Credential that counts') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Finish your program with a credential that signals to employers you are ready to work.') }}
                </p>
            </div>
        </div>
    </x-marketing.section>

    <section aria-label="{{ __('Call to action') }}" class="bg-zinc-900 py-16 sm:py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                {{ __('Ready to get started?') }}
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-300">
                {{ __('Browse our programs to find the trade that fits your goals.') }}
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <flux:button :href="route('public.programs')" variant="primary" wire:navigate>
                    {{ __('Browse programs') }}
                </flux:button>
                @guest
                    <flux:button :href="route('register')" variant="ghost" wire:navigate>
                        {{ __('Create your account') }}
                    </flux:button>
                @endguest
            </div>
        </div>
    </section>
</div>
