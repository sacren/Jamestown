<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;

test('marketing nav shows brand name and logo', function () {
    $html = Blade::render('<x-marketing.nav />');

    expect($html)
        ->toContain(config('app.name'))
        ->toContain(route('home'));
});

test('marketing nav shows sign in and register links for guests', function () {
    $html = Blade::render('<x-marketing.nav />');

    expect($html)
        ->toContain(__('Sign in'))
        ->toContain(__('Register'))
        ->toContain(route('login'))
        ->toContain(route('register'))
        ->not->toContain(__('Dashboard'));
});

test('marketing nav shows dashboard link for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $html = Blade::render('<x-marketing.nav />');

    expect($html)
        ->toContain(__('Dashboard'))
        ->toContain(route('dashboard'))
        ->not->toContain(__('Sign in'))
        ->not->toContain(__('Register'));
});
