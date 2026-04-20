<?php

test('robots.txt exists in the public directory', function () {
    expect(is_file(public_path('robots.txt')))->toBeTrue();
});

test('robots.txt allows public paths for all user agents', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)
        ->toContain('User-agent: *')
        ->toContain('Allow: /');
});

test('robots.txt disallows authenticated areas from crawling', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /dashboard')
        ->toContain('Disallow: /instructor')
        ->toContain('Disallow: /registration')
        ->toContain('Disallow: /settings');
});

test('robots.txt declares a sitemap location', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)->toContain('Sitemap: /sitemap.xml');
});

test('robots.txt does not leak public marketing or catalog routes', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)
        ->not->toContain('Disallow: /programs')
        ->not->toContain('Disallow: /about')
        ->not->toContain('Disallow: /contact')
        ->not->toContain('Disallow: /privacy')
        ->not->toContain('Disallow: /terms')
        ->not->toContain('Disallow: /login')
        ->not->toContain('Disallow: /register');
});
