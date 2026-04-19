<?php

use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Programs')] #[Layout('layouts::marketing', [
    'description' => 'Browse our hands-on trade programs — from welding to HVAC, taught by working professionals.',
])] class extends Component {
    #[Url]
    public string $search = '';

    #[Url]
    public string $durationFilter = '';

    #[Url]
    public string $costFilter = '';

    #[Computed]
    public function programs()
    {
        return Program::query()
            ->where('is_active', true)
            ->withCount('activeCourses')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->durationFilter === 'short', fn ($q) => $q->where('duration_weeks', '<=', 20))
            ->when($this->durationFilter === 'medium', fn ($q) => $q->whereBetween('duration_weeks', [21, 40]))
            ->when($this->durationFilter === 'long', fn ($q) => $q->where('duration_weeks', '>=', 41))
            ->when($this->costFilter === 'under-5k', fn ($q) => $q->where('tuition_cost', '<', 5000))
            ->when($this->costFilter === '5k-15k', fn ($q) => $q->whereBetween('tuition_cost', [5000, 15000]))
            ->when($this->costFilter === 'over-15k', fn ($q) => $q->where('tuition_cost', '>', 15000))
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <x-marketing.section
        :eyebrow="__('Our programs')"
        :heading="__('Programs that build careers')"
        :description="__('Every program is hands-on, taught by working professionals, and designed to land you in the trades.')"
    >
        <div class="mb-8 flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by name or code...') }}"
                    icon="magnifying-glass"
                    :label="__('Search')"
                    label-class="sr-only"
                />
            </div>
            <div class="w-full sm:w-48">
                <flux:select wire:model.live="durationFilter" :label="__('Duration')" label-class="sr-only">
                    <flux:select.option value="">{{ __('Any duration') }}</flux:select.option>
                    <flux:select.option value="short">{{ __('20 weeks or less') }}</flux:select.option>
                    <flux:select.option value="medium">{{ __('21 to 40 weeks') }}</flux:select.option>
                    <flux:select.option value="long">{{ __('41+ weeks') }}</flux:select.option>
                </flux:select>
            </div>
            <div class="w-full sm:w-48">
                <flux:select wire:model.live="costFilter" :label="__('Tuition')" label-class="sr-only">
                    <flux:select.option value="">{{ __('Any tuition') }}</flux:select.option>
                    <flux:select.option value="under-5k">{{ __('Under $5,000') }}</flux:select.option>
                    <flux:select.option value="5k-15k">{{ __('$5,000 to $15,000') }}</flux:select.option>
                    <flux:select.option value="over-15k">{{ __('Over $15,000') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        @if ($this->programs->isEmpty())
            <p class="text-center text-zinc-500 dark:text-zinc-400">
                {{ __('No programs match your filters.') }}
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->programs as $program)
                    <x-marketing.program-card :program="$program" />
                @endforeach
            </div>
        @endif
    </x-marketing.section>
</div>
