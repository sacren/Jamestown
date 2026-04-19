<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['app.debug' => false]);

    Route::get('/__test-503', fn () => abort(503))->middleware('web');
});

test('503 response renders the branded maintenance page', function () {
    $response = $this->get('/__test-503');

    $response->assertStatus(503);
    expect($response->getContent())
        ->toContain('503')
        ->toContain(__('Back in a moment'))
        ->toContain(__(':brand is temporarily offline for scheduled maintenance. Please check back in a few minutes.', ['brand' => config('app.name')]));
});

test('503 page has a single h1 and the skip link', function () {
    $html = $this->get('/__test-503')->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('503 page shows guest CTAs when no user is authenticated', function () {
    $html = $this->get('/__test-503')->getContent();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(__('Browse programs'))
        ->not->toContain(__('Go to dashboard'));
});

test('503 page shows dashboard CTA for authenticated users', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/__test-503')->getContent();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->not->toContain(__('Browse programs'));
});
