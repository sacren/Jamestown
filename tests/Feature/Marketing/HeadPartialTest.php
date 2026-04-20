<?php

use Illuminate\Support\Facades\Blade;

test('head partial renders meta description when provided', function () {
    $html = Blade::render(
        '@include("partials.head")',
        ['description' => 'Vocational training that builds real-world skills.']
    );

    expect($html)->toContain(
        '<meta name="description" content="Vocational training that builds real-world skills."'
    );
});

test('head partial omits meta description when not provided', function () {
    $html = Blade::render('@include("partials.head")');

    expect($html)->not->toContain('name="description"');
});

test('head partial escapes description content', function () {
    $html = Blade::render(
        '@include("partials.head")',
        ['description' => 'Rough & ready "training"']
    );

    expect($html)
        ->toContain('Rough &amp; ready &quot;training&quot;')
        ->not->toContain('content="Rough & ready "training""');
});

test('head partial emits baseline Open Graph tags with default image', function () {
    $html = Blade::render('@include("partials.head")');

    expect($html)
        ->toContain('<meta property="og:type" content="website"')
        ->toContain('<meta property="og:site_name" content="'.config('app.name').'"')
        ->toContain('<meta property="og:url" content="'.url()->current().'"')
        ->toContain('<meta property="og:title" content="'.config('app.name').'"')
        ->toContain('<meta property="og:image" content="'.asset('og-default.svg').'"');
});

test('head partial emits Twitter summary_large_image card with default image', function () {
    $html = Blade::render('@include("partials.head")');

    expect($html)
        ->toContain('<meta name="twitter:card" content="summary_large_image"')
        ->toContain('<meta name="twitter:title" content="'.config('app.name').'"')
        ->toContain('<meta name="twitter:image" content="'.asset('og-default.svg').'"');
});

test('head partial emits og:description and twitter:description when a description is provided', function () {
    $html = Blade::render(
        '@include("partials.head")',
        ['description' => 'Hands-on trade training.']
    );

    expect($html)
        ->toContain('<meta property="og:description" content="Hands-on trade training."')
        ->toContain('<meta name="twitter:description" content="Hands-on trade training."');
});

test('head partial omits og:description and twitter:description when no description is provided', function () {
    $html = Blade::render('@include("partials.head")');

    expect($html)
        ->not->toContain('property="og:description"')
        ->not->toContain('name="twitter:description"');
});

test('head partial uses a custom image when the image prop is provided', function () {
    $html = Blade::render(
        '@include("partials.head")',
        ['image' => 'https://example.test/custom-og.png']
    );

    expect($html)
        ->toContain('<meta property="og:image" content="https://example.test/custom-og.png"')
        ->toContain('<meta name="twitter:image" content="https://example.test/custom-og.png"')
        ->not->toContain('og-default.svg');
});

test('head partial composes a titled og:title and twitter:title when a title is provided', function () {
    $html = Blade::render(
        '@include("partials.head")',
        ['title' => 'About']
    );

    $expected = 'About - '.config('app.name');

    expect($html)
        ->toContain('<meta property="og:title" content="'.$expected.'"')
        ->toContain('<meta name="twitter:title" content="'.$expected.'"');
});
