<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['app.debug' => false]);

    Route::get('/__a11y-404', fn () => abort(404))->middleware('web');
    Route::get('/__a11y-403', fn () => abort(403))->middleware('web');
    Route::get('/__a11y-419', function () {
        throw new TokenMismatchException('CSRF token mismatch.');
    })->middleware('web');
    Route::get('/__a11y-500', function () {
        throw new RuntimeException('Boom.');
    })->middleware('web');
    Route::get('/__a11y-503', fn () => abort(503))->middleware('web');
});

dataset('error pages', [
    'not found (404)' => ['/__a11y-404', 404, 'Page not found'],
    'forbidden (403)' => ['/__a11y-403', 403, 'Access denied'],
    'session expired (419)' => ['/__a11y-419', 419, 'Your session expired'],
    'server error (500)' => ['/__a11y-500', 500, 'Something went wrong'],
    'maintenance (503)' => ['/__a11y-503', 503, 'Back in a moment'],
]);

test('error page renders exactly one h1', function (string $path, int $status, string $heading) {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toMatch('/<h1[^>]*>\s*'.preg_quote(__($heading), '/').'/');
})->with('error pages');

test('error page includes a skip link that targets the main landmark', function (string $path, int $status) {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect($html)
        ->toContain('href="#main"')
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"')
        ->toContain('tabindex="-1"');
})->with('error pages');

test('error page declares the document language', function (string $path, int $status) {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect($html)->toContain('<html lang="'.str_replace('_', '-', app()->getLocale()).'"');
})->with('error pages');

test('error page document title includes the error heading', function (string $path, int $status, string $heading) {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect($html)->toMatch('/<title>\s*'.preg_quote(__($heading), '/').'/');
})->with('error pages');

test('error page renders a single <main> landmark and one <nav> for primary navigation', function (string $path, int $status) {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect(substr_count($html, '<main'))->toBe(1);
    expect($html)->toContain('aria-label="'.__('Main navigation').'"');
})->with('error pages');

test('error page CTAs carry accessible names for every visitor', function (string $path, int $status) {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(__('Browse programs'));
})->with('error pages');

test('error page swaps the secondary CTA to dashboard when authenticated', function (string $path, int $status) {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get($path)->assertStatus($status)->getContent();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->not->toContain(__('Browse programs'));
})->with('error pages');
