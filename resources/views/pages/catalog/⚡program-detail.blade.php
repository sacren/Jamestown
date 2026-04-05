<?php

use App\Models\Program;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Program Details')] class extends Component {
    public Program $program;

    public function mount(Program $program): void
    {
        abort_unless($program->is_active, 404);

        $program->load('activeCourses.prerequisites');
        $this->program = $program;
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('catalog.programs')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Catalog') }}
        </flux:button>

        <div class="flex items-center gap-3">
            <flux:heading size="xl">{{ $program->name }}</flux:heading>
            <flux:badge color="zinc">{{ $program->code }}</flux:badge>
        </div>

        @if ($program->description)
            <flux:text class="mt-2">{{ $program->description }}</flux:text>
        @endif
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Duration') }}</flux:text>
            <flux:heading size="lg">{{ $program->duration_weeks }} {{ __('weeks') }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Total Credits') }}</flux:text>
            <flux:heading size="lg">{{ $program->total_credits_required }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Courses') }}</flux:text>
            <flux:heading size="lg">{{ $program->activeCourses->count() }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Tuition') }}</flux:text>
            <flux:heading size="lg">${{ number_format($program->tuition_cost, 2) }}</flux:heading>
        </div>
    </div>

    <flux:heading size="lg" class="mb-4">{{ __('Courses') }}</flux:heading>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Course Name') }}</flux:table.column>
            <flux:table.column>{{ __('Credits') }}</flux:table.column>
            <flux:table.column>{{ __('Lecture / Lab') }}</flux:table.column>
            <flux:table.column>{{ __('Prerequisites') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($program->activeCourses->sortBy('code') as $course)
                <flux:table.row :key="$course->id">
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $course->code }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">{{ $course->name }}</flux:table.cell>
                    <flux:table.cell>{{ $course->credit_hours }}</flux:table.cell>
                    <flux:table.cell>{{ $course->lecture_hours }}L + {{ $course->lab_hours }}B</flux:table.cell>
                    <flux:table.cell>
                        @if ($course->prerequisites->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach ($course->prerequisites as $prereq)
                                    <flux:badge size="sm" color="amber" inset="top bottom">{{ $prereq->code }}</flux:badge>
                                @endforeach
                            </div>
                        @else
                            <flux:text variant="subtle">{{ __('None') }}</flux:text>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
