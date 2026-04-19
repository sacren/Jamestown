@props([
    'title' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title, 'description' => $description])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-900">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-zinc-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:focus:bg-white dark:focus:text-zinc-900 dark:focus:ring-white dark:focus:ring-offset-zinc-900">
            {{ __('Skip to main content') }}
        </a>

        <x-marketing.nav />

        <main id="main" tabindex="-1" class="focus:outline-none">
            {{ $slot }}
        </main>

        <x-marketing.footer />

        @fluxScripts
    </body>
</html>
