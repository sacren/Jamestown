<?php

use Illuminate\Support\Facades\Blade;

test('split auth layout renders the brand tagline', function () {
    $html = Blade::render('<x-layouts::auth.split>slot</x-layouts::auth.split>');

    expect($html)
        ->toContain(__('Skills that build empires.'))
        ->toContain(config('app.name'));
});

test('split auth layout no longer references inspiring quotes', function () {
    $source = file_get_contents(resource_path('views/layouts/auth/split.blade.php'));

    expect($source)->not->toContain('Inspiring');
});
