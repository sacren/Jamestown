<?php

use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Programs')] #[Layout('layouts::marketing', [
    'description' => 'Browse our hands-on trade programs — from welding to HVAC, taught by working professionals.',
])] class extends Component {
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

<div>
    <x-marketing.section
        :eyebrow="__('Our programs')"
        :heading="__('Programs that build careers')"
        :description="__('Every program is hands-on, taught by working professionals, and designed to land you in the trades.')"
    >
        @if ($this->programs->isEmpty())
            <p class="text-center text-zinc-500 dark:text-zinc-400">
                {{ __('New programs coming soon.') }}
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
