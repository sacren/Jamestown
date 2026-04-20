<?php

use App\Mail\ContactMessageReceipt;
use App\Mail\ContactMessageReceived;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config()->set('mail.admin_address', 'admin@test.example');
});

test('contact page responds with 200 for guests', function () {
    $this->get(route('contact'))->assertOk();
});

test('contact page uses the marketing layout', function () {
    $this->get(route('contact'))
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false);
});

test('contact page renders exactly one h1 with the page heading', function () {
    $response = $this->get(route('contact'));
    $content = $response->getContent();

    expect(substr_count($content, '<h1'))->toBe(1);
    $response->assertSeeInOrder(['<h1', __('Get in touch'), '</h1>'], escape: false);
});

test('contact page sets the meta description', function () {
    $this->get(route('contact'))->assertSee('<meta name="description"', escape: false);
});

test('submitting valid contact form dispatches both mailables, flashes a success message, and redirects', function () {
    Livewire::test('pages::contact')
        ->set('name', 'Jane Trade')
        ->set('email', 'jane@example.com')
        ->set('subject', 'Welding program question')
        ->set('message', 'I would like to know more about the welding program schedule and start dates.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('contact'))
        ->assertSessionHas('contact-sent');

    Mail::assertQueued(ContactMessageReceived::class, function ($mail) {
        return $mail->hasTo('admin@test.example')
            && $mail->senderEmail === 'jane@example.com'
            && $mail->subjectLine === 'Welding program question';
    });

    Mail::assertQueued(ContactMessageReceipt::class, function ($mail) {
        return $mail->hasTo('jane@example.com')
            && $mail->senderName === 'Jane Trade';
    });
});

test('success banner uses the flux:callout success variant with data-test hook', function () {
    session()->flash('contact-sent', 'Thanks for your message.');

    $this->get(route('contact'))
        ->assertSee('data-test="contact-success"', escape: false)
        ->assertSee(__('Message sent'), escape: false);
});

test('invalid submission surfaces validation errors and does not dispatch mail', function () {
    Livewire::test('pages::contact')
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('subject', '')
        ->set('message', 'short')
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'subject', 'message']);

    Mail::assertNothingQueued();
});
