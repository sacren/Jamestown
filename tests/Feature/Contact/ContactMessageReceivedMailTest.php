<?php

use App\Mail\ContactMessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

function makeAdminMail(): ContactMessageReceived
{
    return new ContactMessageReceived(
        senderName: 'Jane Trade',
        senderEmail: 'jane@example.com',
        subjectLine: 'Welding program question',
        body: 'I would like to know more about the welding program schedule.',
    );
}

test('admin contact mailable implements ShouldQueue', function () {
    expect(makeAdminMail())->toBeInstanceOf(ShouldQueue::class);
});

test('admin mailable subject prefixes the brand name and echoes the submitted subject', function () {
    $envelope = makeAdminMail()->envelope();

    expect($envelope->subject)
        ->toContain(config('app.name'))
        ->toContain('Welding program question');
});

test('admin mailable sets replyTo to the sender for direct reply', function () {
    $envelope = makeAdminMail()->envelope();

    expect($envelope->replyTo)->toHaveCount(1);
    expect($envelope->replyTo[0]->address)->toBe('jane@example.com');
    expect($envelope->replyTo[0]->name)->toBe('Jane Trade');
});

test('admin mailable rendered view contains sender identity, subject, and body', function () {
    $rendered = makeAdminMail()->render();

    expect($rendered)
        ->toContain('Jane Trade')
        ->toContain('jane@example.com')
        ->toContain('Welding program question')
        ->toContain('I would like to know more about the welding program schedule.');
});

test('admin mailable heading introduces the contact form message', function () {
    $rendered = makeAdminMail()->render();

    expect($rendered)->toContain(__('New contact message'));
});
