<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function renderErrorLayout(array $data = []): string
{
    $data += ['code' => '404', 'heading' => 'Page not found', 'message' => 'It went somewhere.'];

    return Blade::render(
        '<x-layouts.error :code="$code" :heading="$heading" :message="$message" />',
        $data,
    );
}

test('error layout renders code, heading, and message', function () {
    $html = renderErrorLayout([
        'code' => '404',
        'heading' => 'Page not found',
        'message' => 'We could not find the page you were looking for.',
    ]);

    expect($html)
        ->toContain('404')
        ->toContain('Page not found')
        ->toContain('We could not find the page you were looking for.');
});

test('error layout includes a single h1 for the heading', function () {
    $html = renderErrorLayout(['heading' => 'Page not found']);

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toMatch('/<h1[^>]*>\s*Page not found/');
});

test('error layout includes the skip link and main landmark', function () {
    $html = renderErrorLayout();

    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('error layout renders the marketing nav and footer', function () {
    $html = renderErrorLayout();

    expect($html)
        ->toContain('aria-label="'.__('Main navigation').'"')
        ->toContain(config('app.name'));
});

test('error layout shows Back to home CTA for everyone', function () {
    $html = renderErrorLayout();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(route('home'));
});

test('error layout shows Go to dashboard CTA for authenticated users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $html = renderErrorLayout();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->toContain(route('dashboard'))
        ->not->toContain(__('Browse programs'));
});

test('error layout shows Browse programs CTA for guests', function () {
    $html = renderErrorLayout();

    expect($html)
        ->toContain(__('Browse programs'))
        ->toContain(route('public.programs'))
        ->not->toContain(__('Go to dashboard'));
});

test('error layout renders additional slot content', function () {
    $html = Blade::render(
        '<x-layouts.error code="500" heading="Uh oh" message="Bad times.">Extra context block</x-layouts.error>',
    );

    expect($html)->toContain('Extra context block');
});
