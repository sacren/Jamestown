<?php

use App\Models\Certificate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Certificates')] class extends Component {
    #[Computed]
    public function certificates()
    {
        return Certificate::query()
            ->where('user_id', auth()->id())
            ->with('program')
            ->latest('issued_at')
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Certificates') }}</flux:heading>
        <flux:subheading>{{ __('Your program completion certificates') }}</flux:subheading>
    </div>

    @if ($this->certificates->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No certificates yet') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Complete all courses in a program to earn your certificate.') }}</flux:callout.text>
        </flux:callout>
    @else
        <div class="space-y-4">
            @foreach ($this->certificates as $certificate)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 {{ $certificate->isRevoked() ? 'opacity-60' : '' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="sm">{{ $certificate->program->name }}</flux:heading>
                            <flux:text variant="subtle" class="text-sm">{{ $certificate->certificate_number }}</flux:text>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:text variant="subtle" class="text-sm">{{ $certificate->issued_at->format('M j, Y') }}</flux:text>
                            <flux:badge size="sm" :color="$certificate->status()->color()">
                                {{ $certificate->status()->label() }}
                            </flux:badge>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
