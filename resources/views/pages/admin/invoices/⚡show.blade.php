<?php

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice')] class extends Component {
    public Invoice $invoice;

    public string $amount = '';
    public string $method = '';
    public string $reference = '';
    public string $received_at = '';
    public string $notes = '';

    public bool $showPaymentModal = false;
    public bool $showVoidModal = false;

    public function mount(Invoice $invoice): void
    {
        abort_unless(auth()->user()->can('invoices.view-any'), 403);
        $this->invoice = $invoice->load([
            'enrollment.student',
            'enrollment.section.course',
            'enrollment.section.term',
            'payments.recorder',
        ]);
        $this->received_at = now()->format('Y-m-d\TH:i');
    }

    public function openPaymentModal(): void
    {
        abort_unless(auth()->user()->can('payments.create'), 403);
        $this->resetPaymentForm();
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function recordPayment(): void
    {
        abort_unless(auth()->user()->can('payments.create'), 403);

        $this->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'method' => ['required', 'string', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'reference' => ['nullable', 'string', 'max:255'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string'],
        ]);

        $amount = (float) $this->amount;
        $method = PaymentMethod::from($this->method);

        if ($method === PaymentMethod::Refund && $amount >= 0) {
            $this->addError('amount', __('Refund amount must be negative.'));

            return;
        }

        if ($method !== PaymentMethod::Refund && $amount <= 0) {
            $this->addError('amount', __('Payment amount must be positive.'));

            return;
        }

        Payment::create([
            'invoice_id' => $this->invoice->id,
            'recorded_by' => auth()->id(),
            'amount' => $amount,
            'method' => $method,
            'reference' => $this->reference ?: null,
            'received_at' => $this->received_at,
            'notes' => $this->notes ?: null,
        ]);

        $this->invoice = $this->invoice->fresh(['enrollment.student', 'enrollment.section.course', 'enrollment.section.term', 'payments.recorder']);
        $this->closePaymentModal();
        session()->flash('success', __('Payment recorded successfully.'));
    }

    public function deletePayment(int $paymentId): void
    {
        abort_unless(auth()->user()->can('payments.delete'), 403);

        Payment::where('id', $paymentId)
            ->where('invoice_id', $this->invoice->id)
            ->delete();

        $this->invoice = $this->invoice->fresh(['enrollment.student', 'enrollment.section.course', 'enrollment.section.term', 'payments.recorder']);
        session()->flash('success', __('Payment deleted.'));
    }

    public function voidInvoice(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        if ($this->invoice->isVoided()) {
            return;
        }

        if ($this->invoice->payments()->count() > 0) {
            $this->addError('void', __('Cannot void an invoice with payments.'));

            return;
        }

        $this->invoice->update(['voided_at' => now()]);
        $this->invoice = $this->invoice->fresh();
        $this->showVoidModal = false;
        session()->flash('success', __('Invoice voided.'));
    }

    private function resetPaymentForm(): void
    {
        $this->amount = '';
        $this->method = '';
        $this->reference = '';
        $this->received_at = now()->format('Y-m-d\TH:i');
        $this->notes = '';
        $this->resetErrorBag();
    }

    #[Computed]
    public function canVoid(): bool
    {
        return ! $this->invoice->isVoided()
            && $this->invoice->payments()->count() === 0
            && auth()->user()->can('invoices.manage');
    }

    #[Computed]
    public function canDeletePayments(): bool
    {
        return auth()->user()->can('payments.delete');
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.invoices.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Billing') }}
        </flux:button>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" class="mb-4">
                <flux:callout.text>{{ session('success') }}</flux:callout.text>
            </flux:callout>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ $invoice->invoice_number }}</flux:heading>
                <flux:subheading>
                    <flux:badge :color="$invoice->status()->color()" inset="top bottom">
                        {{ $invoice->status()->label() }}
                    </flux:badge>
                </flux:subheading>
            </div>
            <div class="flex gap-2">
                @if ($this->canVoid)
                    <flux:button variant="danger" wire:click="$set('showVoidModal', true)">
                        {{ __('Void Invoice') }}
                    </flux:button>
                @elseif (! $invoice->isVoided() && $invoice->payments()->count() > 0)
                    <flux:text variant="subtle" class="text-xs">{{ __('Delete all payments before voiding, or refund them instead') }}</flux:text>
                @endif
                @can('payments.create')
                    @if (! $invoice->isVoided())
                        <flux:button variant="primary" wire:click="openPaymentModal">
                            {{ __('Record Payment') }}
                        </flux:button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Student') }}</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Name') }}</dt>
                    <dd>{{ $invoice->enrollment->student->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Email') }}</dt>
                    <dd>{{ $invoice->enrollment->student->email }}</dd>
                </div>
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
                    <flux:table.column>{{ __('Recorded By') }}</flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                    @if ($this->canDeletePayments)
                        <flux:table.column></flux:table.column>
                    @endif
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
                            <flux:table.cell>{{ $payment->recorder?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->notes ?? '—' }}</flux:table.cell>
                            @if ($this->canDeletePayments)
                                <flux:table.cell>
                                    <flux:button variant="ghost" size="sm" wire:click="deletePayment({{ $payment->id }})" wire:confirm="{{ __('Delete this payment?') }}">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </flux:table.cell>
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal wire:model.self="showPaymentModal" class="md:w-96">
        <form wire:submit="recordPayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Record Payment') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Amount') }}</flux:label>
                <flux:input wire:model="amount" type="number" step="0.01" />
                <flux:error name="amount" />
                <flux:description>{{ __('Use a negative amount for refunds.') }}</flux:description>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Method') }}</flux:label>
                <flux:select wire:model="method" placeholder="{{ __('Select method...') }}">
                    <flux:select.option value="">{{ __('Select method...') }}</flux:select.option>
                    @foreach (PaymentMethod::cases() as $m)
                        <flux:select.option value="{{ $m->value }}">{{ $m->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="method" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Reference') }}</flux:label>
                <flux:input wire:model="reference" />
                <flux:error name="reference" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Received At') }}</flux:label>
                <flux:input wire:model="received_at" type="datetime-local" />
                <flux:error name="received_at" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Notes') }}</flux:label>
                <flux:textarea wire:model="notes" />
                <flux:error name="notes" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="closePaymentModal">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __('Save Payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showVoidModal" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Void Invoice?') }}</flux:heading>
            <flux:text>{{ __('This will mark the invoice as voided but preserve its record.') }}</flux:text>
            <flux:error name="void" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="$set('showVoidModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" type="button" wire:click="voidInvoice">{{ __('Void') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
