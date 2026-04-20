<?php

use App\Mail\ContactMessageReceipt;
use App\Mail\ContactMessageReceived;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config()->set('mail.admin_address', 'admin@test.example');
});

test('contact form renders a hidden honeypot field that is not announced to assistive tech', function () {
    $this->get(route('contact'))
        ->assertSee('data-test="contact-honeypot"', escape: false)
        ->assertSee('aria-hidden="true"', escape: false)
        ->assertSee('tabindex="-1"', escape: false);
});

test('submission with a filled honeypot silently discards the message without sending mail', function () {
    Livewire::test('pages::contact')
        ->set('name', 'Spam Bot')
        ->set('email', 'spam@example.com')
        ->set('subject', 'Cheap links!')
        ->set('message', 'Click here for amazing deals online.')
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact-sent');

    Mail::assertNothingQueued();
});

test('legitimate submissions (empty honeypot) still dispatch both mailables', function () {
    Livewire::test('pages::contact')
        ->set('name', 'Real Person')
        ->set('email', 'real@example.com')
        ->set('subject', 'Genuine question')
        ->set('message', 'Hello, I have a real question about your welding program.')
        ->set('website', '')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('contact'));

    Mail::assertQueued(ContactMessageReceived::class);
    Mail::assertQueued(ContactMessageReceipt::class);
});
