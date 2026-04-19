<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['app.debug' => false]);
});

test('404 response renders the branded not-found page', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    expect($response->getContent())
        ->toContain('404')
        ->toContain(__('Page not found'))
        ->toContain(__('We could not find the page you were looking for. It may have moved, or the link may be out of date.'));
});

test('404 page includes a single h1 and the skip link', function () {
    $html = $this->get('/missing')->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('404 page shows guest-facing CTAs for anonymous visitors', function () {
    $html = $this->get('/missing')->getContent();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(__('Browse programs'))
        ->not->toContain(__('Go to dashboard'));
});

test('404 page shows dashboard CTA for authenticated users', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/missing')->getContent();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->toContain(route('dashboard'))
        ->not->toContain(__('Browse programs'));
});

test('404 page is returned when program slug does not exist', function () {
    $response = $this->get('/programs/no-such-program');

    $response->assertNotFound();
    expect($response->getContent())->toContain(__('Page not found'));
});
