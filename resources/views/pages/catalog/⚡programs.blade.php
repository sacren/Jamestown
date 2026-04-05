<?php

use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Program Catalog')] class extends Component {
    #[Computed]
    public function programs()
    {
        return Program::query()
            ->where('is_active', true)
            ->withCount('activeCourses')
            ->orderBy('name')
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Program Catalog') }}</flux:heading>
        <flux:subheading>{{ __('Browse our available trade programs') }}</flux:subheading>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->programs as $program)
            <div wire:key="{{ $program->id }}" class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <div class="mb-3 flex items-center gap-2">
                    <flux:heading size="lg">{{ $program->name }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $program->code }}</flux:badge>
                </div>

                @if ($program->description)
                    <flux:text class="mb-4 line-clamp-3">{{ $program->description }}</flux:text>
                @endif

                <div class="mb-4 space-y-1">
                    <flux:text variant="subtle">{{ __('Duration') }}: {{ $program->duration_weeks }} {{ __('weeks') }}</flux:text>
                    <flux:text variant="subtle">{{ __('Credits') }}: {{ $program->total_credits_required }}</flux:text>
                    <flux:text variant="subtle">{{ __('Courses') }}: {{ $program->active_courses_count }}</flux:text>
                    <flux:text variant="subtle" class="font-semibold">{{ __('Tuition') }}: ${{ number_format($program->tuition_cost, 2) }}</flux:text>
                </div>

                <flux:button variant="primary" size="sm" :href="route('catalog.program', $program)" wire:navigate>
                    {{ __('View Details') }}
                </flux:button>
            </div>
        @endforeach
    </div>
</section>
