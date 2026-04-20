<?php

test('terms page responds with 200 for guests', function () {
    $this->get(route('terms'))->assertOk();
});

test('terms page uses the marketing layout', function () {
    $this->get(route('terms'))
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false);
});

test('terms page renders exactly one h1 with the terms heading', function () {
    $response = $this->get(route('terms'));
    $content = $response->getContent();

    expect(substr_count($content, '<h1'))->toBe(1);
    $response->assertSeeInOrder(['<h1', __('Terms of service'), '</h1>'], escape: false);
});

test('terms page shows a legal-review callout flagging the placeholder status', function () {
    $this->get(route('terms'))
        ->assertSee('data-test="legal-review-callout"', escape: false)
        ->assertSee(__('Placeholder — pending legal review'), escape: false);
});

test('terms page sets the meta description', function () {
    $this->get(route('terms'))->assertSee('<meta name="description"', escape: false);
});

test('terms page covers the expected sections', function () {
    $this->get(route('terms'))
        ->assertSeeInOrder([
            __('Using our services'),
            __('Accounts and eligibility'),
            __('Payments and refunds'),
            __('Changes to these terms'),
        ], escape: false);
});
