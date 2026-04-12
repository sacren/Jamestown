<?php

use App\Models\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Billing')] class extends Component {
    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->whereHas('enrollment', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['enrollment.section.course', 'enrollment.section.term', 'payments'])
            ->orderByDesc('issued_at')
            ->get();
    }

    #[Computed]
    public function totalBalance(): float
    {
        return (float) $this->invoices
            ->filter(fn (Invoice $invoice) => ! $invoice->isVoided())
            ->sum(fn (Invoice $invoice) => max(0, (float) $invoice->balance()));
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Billing') }}</flux:heading>
        <flux:subheading>{{ __('View your invoices and payment history') }}</flux:subheading>
    </div>

    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Balance Due') }}</flux:text>
        <flux:heading size="xl" class="mt-1">${{ number_format($this->totalBalance, 2) }}</flux:heading>
    </div>

    @if ($this->invoices->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle">{{ __('No invoices yet') }}</flux:text>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($this->invoices as $invoice)
                <a href="{{ route('registration.invoices.show', $invoice) }}" wire:navigate class="block rounded-lg border border-zinc-200 bg-white p-6 transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <flux:heading size="lg">{{ $invoice->invoice_number }}</flux:heading>
                            <flux:text variant="subtle" class="text-sm">
                                {{ $invoice->enrollment->section->course->code }} — {{ $invoice->enrollment->section->course->name }}
                            </flux:text>
                        </div>
                        <flux:badge :color="$invoice->status()->color()" inset="top bottom">
                            {{ $invoice->status()->label() }}
                        </flux:badge>
                    </div>

                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Amount Due') }}</dt>
                            <dd>${{ number_format((float) $invoice->amount_due, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Paid') }}</dt>
                            <dd>${{ number_format((float) $invoice->totalPaid(), 2) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-zinc-200 pt-1 dark:border-zinc-700">
                            <dt class="font-semibold">{{ __('Balance') }}</dt>
                            <dd class="font-semibold">${{ number_format((float) $invoice->balance(), 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Due') }}</dt>
                            <dd>{{ $invoice->due_at?->format('M j, Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </a>
            @endforeach
        </div>
    @endif
</section>
