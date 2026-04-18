<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-900">
        <x-marketing.nav />

        <main>
            {{ $slot }}
        </main>

        <x-marketing.footer />

        @fluxScripts
    </body>
</html>
