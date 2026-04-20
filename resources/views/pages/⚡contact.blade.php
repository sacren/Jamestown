<?php

use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactMessageReceipt;
use App\Mail\ContactMessageReceived;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::marketing', [
    'description' => 'Get in touch with Empire Trade School — questions about programs, enrollment, or anything else.',
])] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    public function submit(): mixed
    {
        $validated = $this->validate(
            ContactFormRequest::sharedRules(),
            ContactFormRequest::sharedMessages(),
        );

        Mail::to(config('mail.admin_address'))->send(new ContactMessageReceived(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            subjectLine: $validated['subject'],
            body: $validated['message'],
        ));

        Mail::to($validated['email'])->send(new ContactMessageReceipt(
            senderName: $validated['name'],
            subjectLine: $validated['subject'],
            body: $validated['message'],
        ));

        session()->flash('contact-sent', __('Thanks for your message — we will get back to you as soon as we can.'));

        return $this->redirect(route('contact'), navigate: true);
    }
}; ?>

<div>
    <x-marketing.section
        :eyebrow="__('Contact')"
        :heading="__('Get in touch')"
        :description="__('Questions about programs, enrollment, or anything else — send us a message and we will reply as soon as we can.')"
        :level="1"
    >
        <div class="mx-auto max-w-2xl">
            @if (session('contact-sent'))
                <flux:callout
                    icon="check-circle"
                    color="emerald"
                    class="mb-6"
                    data-test="contact-success"
                >
                    <flux:callout.heading>{{ __('Message sent') }}</flux:callout.heading>
                    <flux:callout.text>{{ session('contact-sent') }}</flux:callout.text>
                </flux:callout>
            @endif

            <form wire:submit="submit" class="space-y-6">
                <flux:input
                    wire:model="name"
                    :label="__('Your name')"
                    autocomplete="name"
                    required
                />

                <flux:input
                    wire:model="email"
                    type="email"
                    :label="__('Email address')"
                    autocomplete="email"
                    required
                />

                <flux:input
                    wire:model="subject"
                    :label="__('Subject')"
                    required
                />

                <flux:textarea
                    wire:model="message"
                    :label="__('Message')"
                    rows="6"
                    required
                />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        {{ __('Send message') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </x-marketing.section>
</div>
