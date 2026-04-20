@php
    $pageTitle = filled($title ?? null)
        ? $title.' - '.config('app.name', 'Laravel')
        : config('app.name', 'Laravel');
    $ogImage = filled($image ?? null) ? $image : asset('og-default.svg');
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $pageTitle }}</title>

@if(filled($description ?? null))
    <meta name="description" content="{{ $description }}" />
@endif

<meta property="og:type" content="website" />
<meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:title" content="{{ $pageTitle }}" />
@if(filled($description ?? null))
    <meta property="og:description" content="{{ $description }}" />
@endif
<meta property="og:image" content="{{ $ogImage }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $pageTitle }}" />
@if(filled($description ?? null))
    <meta name="twitter:description" content="{{ $description }}" />
@endif
<meta name="twitter:image" content="{{ $ogImage }}" />

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
