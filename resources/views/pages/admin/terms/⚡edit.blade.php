<?php

use App\Concerns\TermValidationRules;
use App\Models\Term;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Term')] class extends Component {
    use TermValidationRules;

    #[Locked]
    public int $termId;

    public string $name = '';
    public string $code = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $registration_start = '';
    public string $registration_end = '';
    public bool $is_active = true;

    public function mount(Term $term): void
    {
        $this->termId = $term->id;
        $this->name = $term->name;
        $this->code = $term->code;
        $this->start_date = $term->start_date->format('Y-m-d');
        $this->end_date = $term->end_date->format('Y-m-d');
        $this->registration_start = $term->registration_start->format('Y-m-d');
        $this->registration_end = $term->registration_end->format('Y-m-d');
        $this->is_active = $term->is_active;
    }

    public function updateTerm(): void
    {
        $validated = $this->validate($this->termUpdateRules($this->termId));

        Term::findOrFail($this->termId)->update($validated);

        $this->dispatch('term-updated');
    }

    public function deleteTerm(): void
    {
        Term::findOrFail($this->termId)->delete();

        $this->redirect(route('admin.terms.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.terms.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Terms') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit Term') }}</flux:heading>
        <flux:subheading>{{ __('Update term information') }}</flux:subheading>
    </div>

    <form wire:submit="updateTerm" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Term Name')" type="text" required />
        <flux:input wire:model="code" :label="__('Term Code')" type="text" required />

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
            <flux:button variant="primary" type="submit">{{ __('Update Term') }}</flux:button>

            <x-action-message class="me-3" on="term-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

    <div class="mt-12">
        <flux:separator />
        <div class="mt-6">
            <flux:heading size="lg">{{ __('Delete Term') }}</flux:heading>
            <flux:subheading>{{ __('Permanently remove this term and all its sections.') }}</flux:subheading>
            <flux:button variant="danger" wire:click="deleteTerm" wire:confirm="{{ __('Are you sure you want to delete this term? All associated sections will also be deleted. This action cannot be undone.') }}" class="mt-4">
                {{ __('Delete Term') }}
            </flux:button>
        </div>
    </div>
</section>
