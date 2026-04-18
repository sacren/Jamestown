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
            <x-marketing.program-card :program="$program" />
        @endforeach
    </div>
</section>
