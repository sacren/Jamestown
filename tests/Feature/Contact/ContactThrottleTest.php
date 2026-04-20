<?php

use App\Mail\ContactMessageReceipt;
use App\Mail\ContactMessageReceived;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config()->set('mail.admin_address', 'admin@test.example');
    RateLimiter::clear('contact|127.0.0.1');
});

function submitValidContact(): Testable
{
    return Livewire::test('pages::contact')
        ->set('name', 'Real Person')
        ->set('email', 'real@example.com')
        ->set('subject', 'Genuine question')
        ->set('message', 'Hello, I have a real question about your welding program.')
        ->call('submit');
}

test('contact rate limiter is registered as a named limiter', function () {
    expect(RateLimiter::limiter('contact'))->not->toBeNull();
});

test('three submissions within an hour are accepted', function () {
    submitValidContact()->assertRedirect(route('contact'));
    submitValidContact()->assertRedirect(route('contact'));
    submitValidContact()->assertRedirect(route('contact'));

    Mail::assertQueued(ContactMessageReceived::class, 3);
    Mail::assertQueued(ContactMessageReceipt::class, 3);
});

test('the fourth submission within an hour is throttled with a validation error', function () {
    submitValidContact();
    submitValidContact();
    submitValidContact();

    submitValidContact()->assertHasErrors('message');

    Mail::assertQueued(ContactMessageReceived::class, 3);
});

test('after the limit resets, submissions are accepted again', function () {
    submitValidContact();
    submitValidContact();
    submitValidContact();

    RateLimiter::clear('contact|127.0.0.1');

    submitValidContact()->assertRedirect(route('contact'));

    Mail::assertQueued(ContactMessageReceived::class, 4);
});
