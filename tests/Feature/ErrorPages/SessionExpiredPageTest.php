<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['app.debug' => false]);

    Route::get('/__test-419', function () {
        throw new TokenMismatchException('CSRF token mismatch.');
    })->middleware('web');
});

test('419 response renders the branded session-expired page', function () {
    $response = $this->get('/__test-419');

    $response->assertStatus(419);
    expect($response->getContent())
        ->toContain('419')
        ->toContain(__('Your session expired'))
        ->toContain(__('For your security, the page timed out. Head back and try again — your data is safe.'));
});

test('419 page has a single h1 and the skip link', function () {
    $html = $this->get('/__test-419')->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('419 page shows guest CTAs when no user is authenticated', function () {
    $html = $this->get('/__test-419')->getContent();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(__('Browse programs'))
        ->not->toContain(__('Go to dashboard'));
});

test('419 page shows dashboard CTA for authenticated users', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/__test-419')->getContent();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->not->toContain(__('Browse programs'));
});
