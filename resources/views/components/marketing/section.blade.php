@props([
    'eyebrow' => null,
    'heading' => null,
    'description' => null,
    'id' => null,
    'level' => 2,
])

@php
    $hasHeader = filled($eyebrow) || filled($heading) || filled($description);
    $headingTag = 'h'.(in_array((int) $level, [1, 2, 3, 4], true) ? (int) $level : 2);
@endphp

<section @if($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => 'py-16 sm:py-20 lg:py-24']) }}>
    <div class="mx-auto max-w-7xl px-6">
        @if($hasHeader)
            <div class="mx-auto max-w-3xl text-center">
                @if(filled($eyebrow))
                    <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ $eyebrow }}
                    </p>
                @endif

                @if(filled($heading))
                    <{{ $headingTag }} class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
                        {{ $heading }}
                    </{{ $headingTag }}>
                @endif

                @if(filled($description))
                    <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ $description }}
                    </p>
                @endif
            </div>

            <div class="mt-12">{{ $slot }}</div>
        @else
            {{ $slot }}
        @endif
    </div>
</section>
