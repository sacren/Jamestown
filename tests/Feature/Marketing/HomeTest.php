<?php

use App\Models\User;

test('home page responds with 200 for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('home page uses the marketing layout', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false);
});

test('home hero renders tagline as the primary heading', function () {
    $response = $this->get(route('home'));

    $response->assertSeeInOrder(
        ['<h1', 'Skills that build empires.', '</h1>'],
        escape: false
    );
});

test('home hero shows register and sign in CTAs for guests', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee(__('Get started'), escape: false)
        ->assertSee(__('Sign in'), escape: false)
        ->assertSee(route('register'), escape: false)
        ->assertSee(route('login'), escape: false)
        ->assertDontSee(__('Go to dashboard'), escape: false);
});

test('home hero shows dashboard CTA for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('home'));

    $response
        ->assertSee(__('Go to dashboard'), escape: false)
        ->assertSee(route('dashboard'), escape: false)
        ->assertDontSee(__('Get started'), escape: false);
});

test('home page sets the meta description', function () {
    $response = $this->get(route('home'));

    $response->assertSee('<meta name="description"', escape: false);
});
