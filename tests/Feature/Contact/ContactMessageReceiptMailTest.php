<?php

use App\Mail\ContactMessageReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;

function makeReceiptMail(): ContactMessageReceipt
{
    return new ContactMessageReceipt(
        senderName: 'Jane Trade',
        subjectLine: 'Welding program question',
        body: 'I would like to know more about the welding program schedule.',
    );
}

test('sender receipt mailable implements ShouldQueue', function () {
    expect(makeReceiptMail())->toBeInstanceOf(ShouldQueue::class);
});

test('receipt subject acknowledges the message and names the brand', function () {
    $envelope = makeReceiptMail()->envelope();

    expect($envelope->subject)
        ->toContain('received')
        ->toContain(config('app.name'));
});

test('receipt view thanks the sender by name and echoes their submitted subject and body', function () {
    $rendered = makeReceiptMail()->render();

    expect($rendered)
        ->toContain('Jane Trade')
        ->toContain('Welding program question')
        ->toContain('I would like to know more about the welding program schedule.');
});

test('receipt view links back to the public programs page', function () {
    $rendered = makeReceiptMail()->render();

    expect($rendered)->toContain(route('public.programs'));
});
