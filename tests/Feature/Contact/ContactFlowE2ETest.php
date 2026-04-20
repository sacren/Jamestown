<?php

use App\Mail\ContactMessageReceipt;
use App\Mail\ContactMessageReceived;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config()->set('mail.admin_address', 'admin@test.example');
    RateLimiter::clear('contact|127.0.0.1');
});

test('end-to-end: a visitor sees the form, submits a valid message, and the success banner appears on reload', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee(__('Get in touch'))
        ->assertSee('data-test="contact-honeypot"', escape: false)
        ->assertDontSee('data-test="contact-success"', escape: false);

    Livewire::test('pages::contact')
        ->set('name', 'Jane Trade')
        ->set('email', 'jane@example.com')
        ->set('subject', 'Welding program question')
        ->set('message', 'I am curious about start dates for the welding program.')
        ->call('submit')
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact-sent');

    Mail::assertQueued(ContactMessageReceived::class, function ($mail) {
        return $mail->hasTo('admin@test.example');
    });
    Mail::assertQueued(ContactMessageReceipt::class, function ($mail) {
        return $mail->hasTo('jane@example.com');
    });

    $this->followingRedirects()
        ->get(route('contact'))
        ->assertSee('data-test="contact-success"', escape: false)
        ->assertSee(__('Message sent'));
});

test('end-to-end: a bot filling the honeypot sees the same success banner but no mail is sent', function () {
    Livewire::test('pages::contact')
        ->set('name', 'Spam Bot')
        ->set('email', 'spam@example.com')
        ->set('subject', 'Cheap links!')
        ->set('message', 'Click here for amazing deals online.')
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact-sent');

    Mail::assertNothingQueued();
});

test('end-to-end: after three real submissions, the fourth is throttled and the inbox is protected', function () {
    $payload = [
        'name' => 'Real Person',
        'email' => 'real@example.com',
        'subject' => 'Genuine question',
        'message' => 'Hello, I have a real question about your welding program.',
    ];

    for ($i = 0; $i < 3; $i++) {
        Livewire::test('pages::contact')
            ->set($payload)
            ->call('submit')
            ->assertRedirect(route('contact'));
    }

    Livewire::test('pages::contact')
        ->set($payload)
        ->call('submit')
        ->assertHasErrors('message');

    Mail::assertQueued(ContactMessageReceived::class, 3);
    Mail::assertQueued(ContactMessageReceipt::class, 3);
});
