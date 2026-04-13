<?php

use App\Models\Certificate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Certificate Details')] class extends Component {
    public Certificate $certificate;

    public function mount(Certificate $certificate): void
    {
        abort_unless(auth()->user()->can('certificates.view-any'), 403);
        $this->certificate = $certificate;
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Certificate Details') }}</flux:heading>
</section>
