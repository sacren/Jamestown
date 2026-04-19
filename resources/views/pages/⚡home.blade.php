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

    <x-marketing.section
        id="how-it-works"
        :eyebrow="__('Next steps')"
        :heading="__('How enrollment works')"
        :description="__('Three steps from curious to enrolled.')"
    >
        <ol class="grid gap-8 sm:grid-cols-3">
            <li class="flex flex-col items-center text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-900 text-lg font-bold text-white dark:bg-white dark:text-zinc-900" aria-hidden="true">1</span>
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Browse programs') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Explore our trade programs and pick one that fits your goals.') }}
                </p>
            </li>
            <li class="flex flex-col items-center text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-900 text-lg font-bold text-white dark:bg-white dark:text-zinc-900" aria-hidden="true">2</span>
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Apply online') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Create an account and submit your application in minutes.') }}
                </p>
            </li>
            <li class="flex flex-col items-center text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-900 text-lg font-bold text-white dark:bg-white dark:text-zinc-900" aria-hidden="true">3</span>
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Start learning') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Attend classes taught by working professionals and earn your credential.') }}
                </p>
            </li>
        </ol>
    </x-marketing.section>

    <x-marketing.section
        id="who-its-for"
        :eyebrow="__('Built for')"
        :heading="__('Who it\'s for')"
        :description="__('One platform, three roles, shared success.')"
    >
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:icon name="academic-cap" class="size-8 text-zinc-900 dark:text-white" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Students') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Browse programs, apply online, and track your progress from enrollment to credential.') }}
                </p>
            </div>
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:icon name="user-group" class="size-8 text-zinc-900 dark:text-white" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Instructors') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Manage sections, take attendance, and grade your students in one place.') }}
                </p>
            </div>
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:icon name="briefcase" class="size-8 text-zinc-900 dark:text-white" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Administrators') }}
                </h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Run programs, enrollment, and billing with reports that keep the whole school aligned.') }}
                </p>
            </div>
        </div>
    </x-marketing.section>

    <section aria-label="{{ __('Call to action') }}" class="bg-zinc-900 py-16 sm:py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-4xl px-6 text-center">
            @auth
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ __('Ready to continue?') }}
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-300">
                    {{ __('Your dashboard has everything you need to keep moving forward.') }}
                </p>
                <div class="mt-8">
                    <flux:button :href="route('dashboard')" variant="primary" wire:navigate>
                        {{ __('Go to dashboard') }}
                    </flux:button>
                </div>
            @else
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ __('Ready to build your future?') }}
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-300">
                    {{ __('Create your free account and take the first step toward a hands-on trade career.') }}
                </p>
                <div class="mt-8">
                    <flux:button :href="route('register')" variant="primary" wire:navigate>
                        {{ __('Create your account') }}
                    </flux:button>
                </div>
            @endauth
        </div>
    </section>
</div>
