@props([
    'program',
    'href' => null,
    'level' => 2,
])

<div wire:key="program-card-{{ $program->id }}" class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
    <div class="mb-3 flex items-center gap-2">
        <flux:heading size="lg" :level="$level">{{ $program->name }}</flux:heading>
        <flux:badge size="sm" color="zinc">{{ $program->code }}</flux:badge>
    </div>

    @if ($program->description)
        <flux:text class="mb-4 line-clamp-3">{{ $program->description }}</flux:text>
    @endif

    <div class="mb-4 space-y-1">
        <flux:text variant="subtle">{{ __('Duration') }}: {{ $program->duration_weeks }} {{ __('weeks') }}</flux:text>
        <flux:text variant="subtle">{{ __('Credits') }}: {{ $program->total_credits_required }}</flux:text>
        @isset($program->active_courses_count)
            <flux:text variant="subtle">{{ __('Courses') }}: {{ $program->active_courses_count }}</flux:text>
        @endisset
        <flux:text variant="subtle" class="font-semibold">{{ __('Tuition') }}: ${{ number_format($program->tuition_cost, 2) }}</flux:text>
    </div>

    <flux:button variant="primary" size="sm" :href="$href ?? route('catalog.program', $program)" wire:navigate>
        {{ __('View Details') }}
    </flux:button>
</div>
