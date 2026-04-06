<?php

use App\Concerns\TermValidationRules;
use App\Models\Term;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Term')] class extends Component {
    use TermValidationRules;

    public string $name = '';
    public string $code = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $registration_start = '';
    public string $registration_end = '';
    public bool $is_active = true;

    public function createTerm(): void
    {
        $validated = $this->validate($this->termCreateRules());

        Term::create($validated);

        $this->redirect(route('admin.terms.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.terms.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Terms') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create Term') }}</flux:heading>
        <flux:subheading>{{ __('Add a new academic term') }}</flux:subheading>
    </div>

    <form wire:submit="createTerm" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Term Name')" type="text" required placeholder="e.g. Fall 2026" />
        <flux:input wire:model="code" :label="__('Term Code')" type="text" required placeholder="e.g. FA2026" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="start_date" :label="__('Start Date')" type="date" required />
            <flux:input wire:model="end_date" :label="__('End Date')" type="date" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="registration_start" :label="__('Registration Opens')" type="date" required />
            <flux:input wire:model="registration_end" :label="__('Registration Closes')" type="date" required />
        </div>

        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create Term') }}</flux:button>
        </div>
    </form>
</section>
