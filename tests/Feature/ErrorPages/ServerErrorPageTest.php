<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['app.debug' => false]);

    Route::get('/__test-500', function () {
        throw new RuntimeException('Boom.');
    })->middleware('web');
});

test('500 response renders the branded server-error page', function () {
    $response = $this->get('/__test-500');

    $response->assertStatus(500);
    expect($response->getContent())
        ->toContain('500')
        ->toContain(__('Something went wrong'))
        ->toContain(__('An unexpected error interrupted your request. Our team has been notified — please try again in a moment.'));
});

test('500 page has a single h1 and the skip link', function () {
    $html = $this->get('/__test-500')->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('500 page shows guest CTAs when no user is authenticated', function () {
    $html = $this->get('/__test-500')->getContent();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(__('Browse programs'))
        ->not->toContain(__('Go to dashboard'));
});

test('500 page shows dashboard CTA for authenticated users', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/__test-500')->getContent();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->not->toContain(__('Browse programs'));
});
