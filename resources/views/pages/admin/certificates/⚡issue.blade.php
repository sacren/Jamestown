<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Issue Certificate')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->can('certificates.manage'), 403);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Issue Certificate') }}</flux:heading>
</section>
