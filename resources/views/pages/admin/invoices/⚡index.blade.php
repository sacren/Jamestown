<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Billing')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $termFilter = '';

    #[Url]
    public string $sortField = 'issued_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTermFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    private function baseQuery()
    {
        return Invoice::query()
            ->with(['enrollment.student', 'enrollment.section.course', 'enrollment.section.term'])
            ->withSum('payments as total_paid', 'amount')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('invoice_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('enrollment.student', fn ($s) => $s->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->termFilter, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termFilter)))
            ->when($this->statusFilter, function ($query) {
                match ($this->statusFilter) {
                    InvoiceStatus::Voided->value => $query->whereNotNull('voided_at'),
                    InvoiceStatus::Unpaid->value => $query->whereNull('voided_at')
                        ->whereRaw('COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0) = 0'),
                    InvoiceStatus::Partial->value => $query->whereNull('voided_at')
                        ->whereRaw('COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0) > 0')
                        ->whereRaw('COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0) < amount_due'),
                    InvoiceStatus::Paid->value => $query->whereNull('voided_at')
                        ->whereRaw('COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0) = amount_due'),
                    InvoiceStatus::Overpaid->value => $query->whereNull('voided_at')
                        ->whereRaw('COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0) > amount_due'),
                    default => null,
                };
            });
    }

    #[Computed]
    public function invoices()
    {
        return $this->baseQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $invoices = $this->baseQuery()->get();

        $outstanding = 0.0;
        $collected = 0.0;
        $refunded = 0.0;

        foreach ($invoices as $invoice) {
            if ($invoice->isVoided()) {
                continue;
            }
            $status = $invoice->status();
            if (in_array($status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial], true)) {
                $outstanding += (float) $invoice->balance();
            }
            foreach ($invoice->payments as $payment) {
                $amt = (float) $payment->amount;
                if ($amt >= 0) {
                    $collected += $amt;
                } else {
                    $refunded += abs($amt);
                }
            }
        }

        return [
            'outstanding' => $outstanding,
            'collected' => $collected,
            'refunded' => $refunded,
        ];
    }

    #[Computed]
    public function terms()
    {
        return Term::query()->orderBy('start_date', 'desc')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Billing') }}</flux:heading>
        <flux:subheading>{{ __('Manage student invoices and payments') }}</flux:subheading>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Outstanding') }}</flux:text>
            <flux:heading size="lg">${{ number_format($this->stats['outstanding'], 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Collected') }}</flux:text>
            <flux:heading size="lg">${{ number_format($this->stats['collected'], 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Refunded') }}</flux:text>
            <flux:heading size="lg">${{ number_format($this->stats['refunded'], 2) }}</flux:heading>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by invoice # or student name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach (InvoiceStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('All Terms') }}">
                <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($this->invoices->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle">{{ __('No invoices found') }}</flux:text>
        </div>
    @else
        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortField === 'invoice_number'" :direction="$sortDirection" wire:click="sortBy('invoice_number')">{{ __('Invoice #') }}</flux:table.column>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('Section') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'amount_due'" :direction="$sortDirection" wire:click="sortBy('amount_due')">{{ __('Amount Due') }}</flux:table.column>
                <flux:table.column>{{ __('Paid') }}</flux:table.column>
                <flux:table.column>{{ __('Balance') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'issued_at'" :direction="$sortDirection" wire:click="sortBy('issued_at')">{{ __('Issued') }}</flux:table.column>
                <flux:table.column>{{ __('Due') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->id">
                        <flux:table.cell variant="strong">{{ $invoice->invoice_number }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->enrollment->student->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $invoice->enrollment->section->course->code }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>${{ number_format((float) $invoice->amount_due, 2) }}</flux:table.cell>
                        <flux:table.cell>${{ number_format((float) $invoice->totalPaid(), 2) }}</flux:table.cell>
                        <flux:table.cell>${{ number_format((float) $invoice->balance(), 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$invoice->status()->color()" inset="top bottom">
                                {{ $invoice->status()->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $invoice->issued_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $invoice->due_at?->format('M j, Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button size="sm" :href="route('admin.invoices.show', $invoice)" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
