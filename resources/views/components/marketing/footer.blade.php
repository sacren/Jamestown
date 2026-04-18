<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
            <div class="flex items-center gap-3">
                <x-app-logo-icon class="size-6 fill-current text-zinc-900 dark:text-white" />
                <div>
                    <div class="font-semibold text-zinc-900 dark:text-white">{{ config('app.name') }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Skills that build empires.') }}</div>
                </div>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</footer>
