<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['app.debug' => false]);

    Route::get('/__test-403', fn () => abort(403))->middleware('web');
});

test('403 response renders the branded access-denied page', function () {
    $response = $this->get('/__test-403');

    $response->assertForbidden();
    expect($response->getContent())
        ->toContain('403')
        ->toContain(__('Access denied'))
        ->toContain(__('You do not have permission to view this page. If you think this is a mistake, please contact an administrator.'));
});

test('403 page has a single h1 and the skip link', function () {
    $html = $this->get('/__test-403')->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('403 page shows Back to home for everyone', function () {
    $html = $this->get('/__test-403')->getContent();

    expect($html)
        ->toContain(__('Back to home'))
        ->toContain(route('home'));
});

test('403 page shows dashboard CTA for authenticated users', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/__test-403')->getContent();

    expect($html)
        ->toContain(__('Go to dashboard'))
        ->not->toContain(__('Browse programs'));
});

test('403 page renders when a student hits an admin route', function () {
    $student = User::factory()->asStudent()->create();

    $html = $this->actingAs($student)->get(route('admin.programs.index'))->getContent();

    expect($html)->toContain(__('Access denied'));
});
