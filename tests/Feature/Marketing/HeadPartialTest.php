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
