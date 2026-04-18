<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\Term;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::marketing', [
    'description' => 'Hands-on trade training that builds careers — taught by working professionals at Empire Trade School.',
])] class extends Component {
    private const CACHE_TTL_SECONDS = 600;

    #[Computed]
    public function programCount(): int
    {
        return Cache::remember(
            'home.program-count',
            self::CACHE_TTL_SECONDS,
            fn () => Program::query()->where('is_active', true)->count(),
        );
    }

    #[Computed]
    public function courseCount(): int
    {
        return Cache::remember(
            'home.course-count',
            self::CACHE_TTL_SECONDS,
            fn () => Course::query()->where('is_active', true)->count(),
        );
    }

    #[Computed]
    public function currentTermName(): ?string
    {
        return Cache::remember('home.current-term-name', self::CACHE_TTL_SECONDS, function () {
            $today = today();

            return Term::query()
                ->where('is_active', true)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->orderByDesc('start_date')
                ->value('name')
                ?? Term::query()
                    ->where('is_active', true)
                    ->where('start_date', '>', $today)
                    ->orderBy('start_date')
                    ->value('name');
        });
    }

    #[Computed]
    public function featuredPrograms()
    {
        return Program::query()
            ->where('is_active', true)
            ->withCount('activeCourses')
            ->orderByDesc('active_courses_count')
            ->orderBy('name')
            ->limit(3)
            ->get();
    }
}; ?>

<div>
    <section class="relative isolate overflow-hidden bg-gradient-to-b from-white to-zinc-50 py-20 sm:py-28 lg:py-32 dark:from-zinc-900 dark:to-zinc-950">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl dark:text-white">
                {{ __('Skills that build empires.') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 sm:text-xl dark:text-zinc-300">
                {{ __(':brand equips you with the hands-on trade skills employers need — taught by working professionals.', ['brand' => config('app.name')]) }}
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <flux:button :href="route('dashboard')" variant="primary" wire:navigate>
                        {{ __('Go to dashboard') }}
                    </flux:button>
                @else
                    <flux:button :href="route('register')" variant="primary" wire:navigate>
                        {{ __('Get started') }}
                    </flux:button>
                    <flux:button :href="route('login')" variant="ghost" wire:navigate>
                        {{ __('Sign in') }}
                    </flux:button>
                @endauth
            </div>
        </div>
    </section>

    <x-marketing.section aria-label="{{ __('Program statistics') }}" class="!py-10">
        <dl class="grid grid-cols-1 gap-8 text-center sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Programs') }}
                </dt>
                <dd class="mt-2 text-4xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->programCount }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Courses') }}
                </dt>
                <dd class="mt-2 text-4xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->courseCount }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Current term') }}
                </dt>
                <dd class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                    {{ $this->currentTermName ?? __('Rolling enrollment') }}
                </dd>
            </div>
        </dl>
    </x-marketing.section>

    <x-marketing.section
        id="featured"
        :eyebrow="__('Explore')"
        :heading="__('Featured programs')"
        :description="__('A look at some of the programs our students are building careers with.')"
    >
        @if ($this->featuredPrograms->isEmpty())
            <p class="text-center text-zinc-500 dark:text-zinc-400">
                {{ __('New programs coming soon.') }}
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->featuredPrograms as $program)
                    <x-marketing.program-card :program="$program" />
                @endforeach
            </div>
        @endif
    </x-marketing.section>
</div>
