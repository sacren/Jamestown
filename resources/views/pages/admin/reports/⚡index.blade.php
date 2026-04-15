<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->can('view-reports'), 403);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Reports & Analytics') }}</flux:heading>
        <flux:subheading>{{ __('View detailed reports and analytics for the school') }}</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.reports.enrollment') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-6 transition hover:border-blue-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-600">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="clipboard-document-list" class="size-6 text-blue-500" />
                <flux:heading size="lg">{{ __('Enrollment') }}</flux:heading>
            </div>
            <flux:text variant="subtle">{{ __('Enrollment statistics by term, program, and section with status breakdowns.') }}</flux:text>
        </a>

        <a href="{{ route('admin.reports.attendance') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-6 transition hover:border-green-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-green-600">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="check-badge" class="size-6 text-green-500" />
                <flux:heading size="lg">{{ __('Attendance') }}</flux:heading>
            </div>
            <flux:text variant="subtle">{{ __('Attendance rates by section, at-risk students, and trend analysis.') }}</flux:text>
        </a>

        <a href="{{ route('admin.reports.grades') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-6 transition hover:border-amber-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-amber-600">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="chart-bar" class="size-6 text-amber-500" />
                <flux:heading size="lg">{{ __('Grade Performance') }}</flux:heading>
            </div>
            <flux:text variant="subtle">{{ __('Grade distributions, section averages, and academic performance metrics.') }}</flux:text>
        </a>

        <a href="{{ route('admin.reports.financial') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-6 transition hover:border-emerald-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-emerald-600">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="banknotes" class="size-6 text-emerald-500" />
                <flux:heading size="lg">{{ __('Financial') }}</flux:heading>
            </div>
            <flux:text variant="subtle">{{ __('Revenue, outstanding balances, payment methods, and collection rates.') }}</flux:text>
        </a>

        <a href="{{ route('admin.reports.program-completion') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-6 transition hover:border-purple-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-purple-600">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="academic-cap" class="size-6 text-purple-500" />
                <flux:heading size="lg">{{ __('Program Completion') }}</flux:heading>
            </div>
            <flux:text variant="subtle">{{ __('Completion rates, certificate counts, and program progress tracking.') }}</flux:text>
        </a>
    </div>
</section>
