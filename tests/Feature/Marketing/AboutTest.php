<?php

use App\Models\User;

test('about page responds with 200 for guests', function () {
    $this->get(route('about'))->assertOk();
});

test('about page uses the marketing layout', function () {
    $this->get(route('about'))
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false);
});

test('about hero renders the tagline as the primary heading', function () {
    $this->get(route('about'))
        ->assertSeeInOrder(['<h1', 'Skills that build empires.', '</h1>'], escape: false);
});

test('about page has exactly one h1', function () {
    $content = $this->get(route('about'))->getContent();

    expect(substr_count($content, '<h1'))->toBe(1);
});

test('about page sets the meta description', function () {
    $this->get(route('about'))->assertSee('<meta name="description"', escape: false);
});

test('about page renders the mission section with three supporting cards', function () {
    $this->get(route('about'))
        ->assertSeeInOrder([
            __('Our mission'),
            __('Hands-on training, real careers'),
            __('Practical instruction'),
            __('Working professionals'),
            __('Credential that counts'),
        ], escape: false);
});

test('about final CTA links guests to programs and register', function () {
    $this->get(route('about'))
        ->assertSee(__('Browse programs'), escape: false)
        ->assertSee(__('Create your account'), escape: false)
        ->assertSee(route('public.programs'), escape: false)
        ->assertSee(route('register'), escape: false);
});

test('about final CTA hides register link for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('about'))
        ->assertSee(__('Browse programs'), escape: false)
        ->assertDontSee(__('Create your account'), escape: false);
});
