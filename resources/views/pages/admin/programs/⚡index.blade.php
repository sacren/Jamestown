<?php

use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Program Management')] class extends Component {
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

    public function toggleActive(int $programId): void
    {
        $program = Program::findOrFail($programId);
        $program->update(['is_active' => ! $program->is_active]);
    }

    public function deleteProgram(int $programId): void
    {
        Program::findOrFail($programId)->delete();
    }

    #[Computed]
    public function programs()
    {
        return Program::query()
            ->withCount('courses')
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
            <flux:heading size="xl">{{ __('Program Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage academic programs') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.programs.create')" wire:navigate icon="plus">
            {{ __('Add Program') }}
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

    <flux:table :paginate="$this->programs">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Courses') }}</flux:table.column>
            <flux:table.column>{{ __('Duration') }}</flux:table.column>
            <flux:table.column>{{ __('Tuition') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->programs as $program)
                <flux:table.row :key="$program->id">
                    <flux:table.cell variant="strong">{{ $program->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $program->code }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $program->courses_count }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $program->duration_weeks }} {{ __('weeks') }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">${{ number_format($program->tuition_cost, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$program->is_active ? 'green' : 'zinc'" inset="top bottom">
                            {{ $program->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('admin.programs.edit', $program)" wire:navigate />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteProgram({{ $program->id }})" wire:confirm="{{ __('Are you sure you want to delete this program? All associated courses will also be deleted.') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
