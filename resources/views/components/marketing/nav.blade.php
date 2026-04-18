<header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-4" aria-label="{{ __('Main navigation') }}">
        <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
            <x-app-logo-icon class="size-7 fill-current text-zinc-900 dark:text-white" />
            <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
        </a>

        <div class="flex items-center gap-3">
            @auth
                <flux:button :href="route('dashboard')" variant="primary" size="sm" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:button>
            @else
                <flux:button :href="route('login')" variant="ghost" size="sm" wire:navigate>
                    {{ __('Sign in') }}
                </flux:button>
                <flux:button :href="route('register')" variant="primary" size="sm" wire:navigate>
                    {{ __('Register') }}
                </flux:button>
            @endauth
        </div>
    </nav>
</header>
