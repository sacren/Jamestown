<?php

use Illuminate\Support\Facades\Blade;

test('marketing footer shows brand name and tagline', function () {
    $html = Blade::render('<x-marketing.footer />');

    expect($html)
        ->toContain(config('app.name'))
        ->toContain(__('Skills that build empires.'));
});

test('marketing footer shows copyright with current year', function () {
    $html = Blade::render('<x-marketing.footer />');

    expect($html)
        ->toContain((string) now()->year)
        ->toContain(__('All rights reserved.'));
});
