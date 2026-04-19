@props([
    'items' => [],
])

@if (filled($items))
    <nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->merge(['class' => 'mb-6']) }}>
        <flux:breadcrumbs>
            @foreach ($items as $item)
                <flux:breadcrumbs.item :href="$item['href'] ?? null">
                    {{ $item['label'] }}
                </flux:breadcrumbs.item>
            @endforeach
        </flux:breadcrumbs>
    </nav>
@endif
