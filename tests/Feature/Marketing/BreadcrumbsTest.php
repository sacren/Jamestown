<?php

use Illuminate\Support\Facades\Blade;

test('breadcrumbs renders each item label', function () {
    $items = [
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'Programs', 'href' => '/programs'],
        ['label' => 'Welding Technology'],
    ];

    $html = Blade::render('<x-marketing.breadcrumbs :items="$items" />', compact('items'));

    expect($html)
        ->toContain('Home')
        ->toContain('Programs')
        ->toContain('Welding Technology');
});

test('breadcrumbs links items with href', function () {
    $items = [
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'Programs', 'href' => '/programs'],
    ];

    $html = Blade::render('<x-marketing.breadcrumbs :items="$items" />', compact('items'));

    expect($html)
        ->toContain('href="/"')
        ->toContain('href="/programs"');
});

test('breadcrumbs renders final item without an anchor tag', function () {
    $items = [
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'Current Page'],
    ];

    $html = Blade::render('<x-marketing.breadcrumbs :items="$items" />', compact('items'));

    expect($html)
        ->toContain('Current Page')
        ->not->toMatch('/<a [^>]*>\s*Current Page/');
});

test('breadcrumbs wraps output in a nav with breadcrumb aria label', function () {
    $items = [['label' => 'Home', 'href' => '/']];

    $html = Blade::render('<x-marketing.breadcrumbs :items="$items" />', compact('items'));

    expect($html)
        ->toContain('<nav')
        ->toContain('aria-label="'.__('Breadcrumb').'"');
});

test('breadcrumbs renders nothing when items are empty', function () {
    $html = Blade::render('<x-marketing.breadcrumbs :items="[]" />');

    expect(trim($html))->toBe('');
});
