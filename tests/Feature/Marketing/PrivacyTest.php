<?php

test('privacy page responds with 200 for guests', function () {
    $this->get(route('privacy'))->assertOk();
});

test('privacy page uses the marketing layout', function () {
    $this->get(route('privacy'))
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false);
});

test('privacy page renders exactly one h1 with the policy heading', function () {
    $response = $this->get(route('privacy'));
    $content = $response->getContent();

    expect(substr_count($content, '<h1'))->toBe(1);
    $response->assertSeeInOrder(['<h1', __('Privacy policy'), '</h1>'], escape: false);
});

test('privacy page shows a legal-review callout flagging the placeholder status', function () {
    $this->get(route('privacy'))
        ->assertSee('data-test="legal-review-callout"', escape: false)
        ->assertSee(__('Placeholder — pending legal review'), escape: false);
});

test('privacy page sets the meta description', function () {
    $this->get(route('privacy'))->assertSee('<meta name="description"', escape: false);
});

test('privacy page covers the expected policy sections', function () {
    $this->get(route('privacy'))
        ->assertSeeInOrder([
            __('Information we collect'),
            __('How we use information'),
            __('Sharing and disclosure'),
            __('Contact us'),
        ], escape: false);
});
