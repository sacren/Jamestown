<?php

use App\Models\Invoice;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice')] class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        abort_unless((int) $invoice->enrollment->user_id === (int) auth()->id(), 403);

        $this->invoice = $invoice->load([
            'enrollment.student',
            'enrollment.section.course',
            'enrollment.section.term',
            'payments',
        ]);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('registration.invoices.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to My Billing') }}
        </flux:button>

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ $invoice->invoice_number }}</flux:heading>
                <flux:subheading>
                    <flux:badge :color="$invoice->status()->color()" inset="top bottom">
                        {{ $invoice->status()->label() }}
                    </flux:badge>
                </flux:subheading>
            </div>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Course') }}</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Course') }}</dt>
                    <dd>{{ $invoice->enrollment->section->course->code }} — {{ $invoice->enrollment->section->course->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Section') }}</dt>
                    <dd>{{ $invoice->enrollment->section->section_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Term') }}</dt>
                    <dd>{{ $invoice->enrollment->section->term->name }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Amounts') }}</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Amount Due') }}</dt>
                    <dd>${{ number_format((float) $invoice->amount_due, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Total Paid') }}</dt>
                    <dd>${{ number_format((float) $invoice->totalPaid(), 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    <dt class="font-semibold">{{ __('Balance') }}</dt>
                    <dd class="text-lg font-semibold">${{ number_format((float) $invoice->balance(), 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Issued') }}</dt>
                    <dd>{{ $invoice->issued_at->format('M j, Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Due') }}</dt>
                    <dd>{{ $invoice->due_at?->format('M j, Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Payment History') }}</flux:heading>
        @if ($invoice->payments->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text variant="subtle">{{ __('No payments recorded yet') }}</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column>{{ __('Amount') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($invoice->payments as $payment)
                        <flux:table.row :key="$payment->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $payment->received_at->format('M j, Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->method->label() }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->reference ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="{{ ((float) $payment->amount) < 0 ? 'text-red-600' : '' }}">
                                ${{ number_format((float) $payment->amount, 2) }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</section>
