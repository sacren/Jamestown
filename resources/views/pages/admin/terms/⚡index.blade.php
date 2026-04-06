<?php

use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Term Management')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $termId): void
    {
        $term = Term::findOrFail($termId);
        $term->update(['is_active' => ! $term->is_active]);
    }

    public function deleteTerm(int $termId): void
    {
        Term::findOrFail($termId)->delete();
    }

    #[Computed]
    public function terms()
    {
        return Term::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->latest()
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Term Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage academic terms') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.terms.create')" wire:navigate icon="plus">
            {{ __('Add Term') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or code...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->terms">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Start Date') }}</flux:table.column>
            <flux:table.column>{{ __('End Date') }}</flux:table.column>
            <flux:table.column>{{ __('Registration') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->terms as $term)
                <flux:table.row :key="$term->id">
                    <flux:table.cell variant="strong">{{ $term->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $term->code }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $term->start_date->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $term->end_date->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $term->registration_start->format('M j') }} - {{ $term->registration_end->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$term->is_active ? 'green' : 'zinc'" inset="top bottom">
                            {{ $term->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('admin.terms.edit', $term)" wire:navigate />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteTerm({{ $term->id }})" wire:confirm="{{ __('Are you sure you want to delete this term? All associated sections will also be deleted.') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
