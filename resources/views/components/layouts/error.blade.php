@props([
    'code' => null,
    'heading' => null,
    'message' => null,
    'title' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', [
            'title' => $title ?? $heading ?? $code,
            'description' => $description,
        ])
    </head>
    <body class="flex min-h-screen flex-col bg-white antialiased dark:bg-neutral-900">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-zinc-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:focus:bg-white dark:focus:text-zinc-900 dark:focus:ring-white dark:focus:ring-offset-zinc-900">
            {{ __('Skip to main content') }}
        </a>

        <x-marketing.nav />

        <main id="main" tabindex="-1" class="flex flex-1 items-center justify-center px-6 py-16 focus:outline-none sm:py-24">
            <div class="mx-auto max-w-2xl text-center">
                @if(filled($code))
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                        {{ __('Error') }} {{ $code }}
                    </p>
                @endif

                @if(filled($heading))
                    <h1 class="mt-2 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        {{ $heading }}
                    </h1>
                @endif

                @if(filled($message))
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ $message }}
                    </p>
                @endif

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <flux:button :href="route('home')" variant="primary" wire:navigate>
                        {{ __('Back to home') }}
                    </flux:button>
                    @auth
                        <flux:button :href="route('dashboard')" variant="ghost" wire:navigate>
                            {{ __('Go to dashboard') }}
                        </flux:button>
                    @else
                        <flux:button :href="route('public.programs')" variant="ghost" wire:navigate>
                            {{ __('Browse programs') }}
                        </flux:button>
                    @endauth
                </div>

                @if(isset($slot) && trim($slot) !== '')
                    <div class="mt-10">{{ $slot }}</div>
                @endif
            </div>
        </main>

        <x-marketing.footer />

        @fluxScripts
    </body>
</html>
