<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::marketing', [
    'description' => 'Terms of service for Empire Trade School — a placeholder pending legal review.',
])] class extends Component {
}; ?>

<div>
    <x-marketing.section
        :eyebrow="__('Legal')"
        :heading="__('Terms of service')"
        :description="__('The rules that govern your use of :brand, our website, and our programs.', ['brand' => config('app.name')])"
        :level="1"
    >
        <div class="mx-auto max-w-3xl space-y-6">
            <flux:callout icon="exclamation-triangle" color="amber" data-test="legal-review-callout">
                <flux:callout.heading>{{ __('Placeholder — pending legal review') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('These terms are a placeholder and have not yet been reviewed by legal counsel. Do not rely on them as a binding agreement.') }}
                </flux:callout.text>
            </flux:callout>

            <div class="prose prose-zinc max-w-none dark:prose-invert">
                <h2>{{ __('Using our services') }}</h2>
                <p>
                    {{ __('By creating an account or using our website, you agree to use our services lawfully and in accordance with these terms.') }}
                </p>

                <h2>{{ __('Accounts and eligibility') }}</h2>
                <p>
                    {{ __('You are responsible for keeping your account credentials secure and for all activity that takes place under your account.') }}
                </p>

                <h2>{{ __('Payments and refunds') }}</h2>
                <p>
                    {{ __('Program tuition, fees, and refund eligibility are described in your enrollment agreement. These terms do not override that agreement.') }}
                </p>

                <h2>{{ __('Changes to these terms') }}</h2>
                <p>
                    {{ __('We may update these terms from time to time. Continued use of our services after an update means you accept the revised terms.') }}
                </p>
            </div>
        </div>
    </x-marketing.section>
</div>
