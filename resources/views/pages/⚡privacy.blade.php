<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::marketing', [
    'description' => 'Privacy policy for Empire Trade School — a placeholder pending legal review.',
])] class extends Component {
}; ?>

<div>
    <x-marketing.section
        :eyebrow="__('Legal')"
        :heading="__('Privacy policy')"
        :description="__('How :brand collects, uses, and protects the information you share with us.', ['brand' => config('app.name')])"
        :level="1"
    >
        <div class="mx-auto max-w-3xl space-y-6">
            <flux:callout icon="exclamation-triangle" color="amber" data-test="legal-review-callout">
                <flux:callout.heading>{{ __('Placeholder — pending legal review') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('This policy is a placeholder and has not yet been reviewed by legal counsel. Do not rely on it as a final statement of our privacy practices.') }}
                </flux:callout.text>
            </flux:callout>

            <div class="prose prose-zinc max-w-none dark:prose-invert">
                <h2>{{ __('Information we collect') }}</h2>
                <p>
                    {{ __('We collect the information you provide when you create an account, apply to a program, or contact us — including your name, email address, and any details you share in a message or application.') }}
                </p>

                <h2>{{ __('How we use information') }}</h2>
                <p>
                    {{ __('We use your information to process applications, deliver instruction, administer enrollment and billing, and communicate with you about your account.') }}
                </p>

                <h2>{{ __('Sharing and disclosure') }}</h2>
                <p>
                    {{ __('We do not sell your personal information. We share information only with service providers who help us operate the school and when required by law.') }}
                </p>

                <h2>{{ __('Contact us') }}</h2>
                <p>
                    {{ __('Questions about this policy? Reach out through our contact page and we will respond as soon as we can.') }}
                </p>
            </div>
        </div>
    </x-marketing.section>
</div>
