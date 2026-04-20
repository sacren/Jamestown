<?php

use App\Models\Program;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Program Details')] class extends Component {
    public Program $program;

    public function mount(Program $publicProgram): void
    {
        $publicProgram->load('activeCourses.prerequisites');
        $this->program = $publicProgram;
    }

    public function apply(): mixed
    {
        session()->flash('interested_program', $this->program->id);

        return $this->redirect(route('register'), navigate: true);
    }

    public function breadcrumbs(): array
    {
        return [
            ['label' => __('Home'), 'href' => route('home')],
            ['label' => __('Programs'), 'href' => route('public.programs')],
            ['label' => $this->program->name],
        ];
    }

    public function metaDescription(): string
    {
        if (filled($this->program->description)) {
            return Str::limit($this->program->description, 155);
        }

        return __(':name — a hands-on trade program at :brand.', [
            'name' => $this->program->name,
            'brand' => config('app.name'),
        ]);
    }

    public function render()
    {
        return $this->view()->layout('layouts::marketing', [
            'description' => $this->metaDescription(),
        ]);
    }
}; ?>

<div>
    <div class="mx-auto max-w-7xl px-6 pt-6">
        <x-marketing.breadcrumbs :items="$this->breadcrumbs()" />
    </div>

    <x-marketing.section
        :eyebrow="$program->code"
        :heading="$program->name"
        :description="$program->description"
        :level="1"
    >
        <div class="mx-auto max-w-5xl">
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

            <div class="mb-8 flex flex-wrap gap-3">
                <flux:button variant="primary" wire:click="apply">
                    {{ __('Apply to this program') }}
                </flux:button>
                <flux:button variant="ghost" :href="route('public.programs')" icon="arrow-left" wire:navigate>
                    {{ __('Back to all programs') }}
                </flux:button>
            </div>

            <flux:heading size="lg" :level="2" class="mb-4">{{ __('Courses in this program') }}</flux:heading>

            @if ($program->activeCourses->isEmpty())
                <flux:text variant="subtle">{{ __('Course details will be published soon.') }}</flux:text>
            @else
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
            @endif
        </div>
    </x-marketing.section>
</div>
