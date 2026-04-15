<?php

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Financial Report')] class extends Component {
    #[Url]
    public string $termId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view-reports'), 403);

        if ($this->termId === '') {
            $activeTerm = Term::where('is_active', true)->latest('start_date')->first();
            if ($activeTerm) {
                $this->termId = (string) $activeTerm->id;
            }
        }
    }

    #[Computed]
    public function terms()
    {
        return Term::query()->orderBy('start_date', 'desc')->get();
    }

    private function baseInvoiceQuery()
    {
        return Invoice::query()
            ->whereNull('voided_at')
            ->when($this->termId, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termId)));
    }

    #[Computed]
    public function revenueStats(): array
    {
        $invoices = $this->baseInvoiceQuery()->with('payments')->get();

        $totalInvoiced = 0.0;
        $netRevenue = 0.0;

        foreach ($invoices as $invoice) {
            $totalInvoiced += (float) $invoice->amount_due;
            foreach ($invoice->payments as $payment) {
                $netRevenue += (float) $payment->amount;
            }
        }

        $outstanding = $totalInvoiced - $netRevenue;
        $collectionRate = $totalInvoiced > 0 ? round($netRevenue / $totalInvoiced * 100, 1) : 0;

        return [
            'total_invoiced' => $totalInvoiced,
            'net_revenue' => $netRevenue,
            'outstanding' => $outstanding,
            'collection_rate' => $collectionRate,
        ];
    }

    #[Computed]
    public function paymentMethodBreakdown()
    {
        $payments = Payment::query()
            ->whereHas('invoice', function ($q) {
                $q->whereNull('voided_at');
                if ($this->termId) {
                    $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termId));
                }
            })
            ->where('method', '!=', PaymentMethod::Refund)
            ->get();

        $grouped = $payments->groupBy(fn ($p) => $p->method->value);

        $positiveTotal = $payments->sum(fn ($p) => (float) $p->amount);

        $rows = $grouped->map(function ($methodPayments, $methodValue) use ($positiveTotal) {
            $total = $methodPayments->sum(fn ($p) => (float) $p->amount);

            return (object) [
                'method' => PaymentMethod::from($methodValue),
                'count' => $methodPayments->count(),
                'total' => $total,
                'percentage' => $positiveTotal > 0 ? round($total / $positiveTotal * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values();

        // Refund total
        $refundTotal = Payment::query()
            ->whereHas('invoice', function ($q) {
                $q->whereNull('voided_at');
                if ($this->termId) {
                    $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termId));
                }
            })
            ->where('method', PaymentMethod::Refund)
            ->sum('amount');

        return (object) [
            'rows' => $rows,
            'refund_total' => (float) $refundTotal,
        ];
    }

    #[Computed]
    public function outstandingInvoices()
    {
        return $this->baseInvoiceQuery()
            ->with(['payments', 'enrollment.student', 'enrollment.section.course'])
            ->get()
            ->filter(fn ($invoice) => (float) $invoice->balance() > 0)
            ->sortByDesc(fn ($invoice) => (float) $invoice->balance())
            ->take(10)
            ->values();
    }

    #[Computed]
    public function programRevenue()
    {
        $invoices = $this->baseInvoiceQuery()
            ->with(['payments', 'enrollment.section.course.program'])
            ->get();

        return $invoices
            ->groupBy(fn ($i) => $i->enrollment->section->course->program_id)
            ->map(function ($group) {
                $program = $group->first()->enrollment->section->course->program;
                $invoiced = $group->sum(fn ($i) => (float) $i->amount_due);
                $collected = 0.0;
                foreach ($group as $invoice) {
                    foreach ($invoice->payments as $payment) {
                        $collected += (float) $payment->amount;
                    }
                }

                return (object) [
                    'program' => $program,
                    'invoiced' => $invoiced,
                    'collected' => $collected,
                    'outstanding' => $invoiced - $collected,
                ];
            })
            ->sortBy(fn ($row) => $row->program->name)
            ->values();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-sm">
            <a href="{{ route('admin.reports.index') }}" wire:navigate class="text-blue-500 hover:underline">{{ __('Reports') }}</a>
            <span class="text-zinc-400">/</span>
            <span>{{ __('Financial Report') }}</span>
        </div>
        <flux:heading size="xl">{{ __('Financial Report') }}</flux:heading>
        <flux:subheading>{{ __('Revenue, outstanding balances, and payment analytics') }}</flux:subheading>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="termId" placeholder="{{ __('All Terms') }}">
                <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Invoiced') }}</flux:text>
            <flux:heading size="lg">${{ number_format($this->revenueStats['total_invoiced'], 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Net Revenue') }}</flux:text>
            <flux:heading size="lg">${{ number_format($this->revenueStats['net_revenue'], 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Outstanding Balance') }}</flux:text>
            <flux:heading size="lg">${{ number_format($this->revenueStats['outstanding'], 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Collection Rate') }}</flux:text>
            <flux:heading size="lg">{{ $this->revenueStats['collection_rate'] }}%</flux:heading>
        </div>
    </div>

    @if ($this->revenueStats['total_invoiced'] <= 0)
        <flux:callout>
            <flux:callout.heading>{{ __('No invoices found') }}</flux:callout.heading>
            <flux:callout.text>{{ __('No financial data matches the selected filters.') }}</flux:callout.text>
        </flux:callout>
    @else
        @if ($this->paymentMethodBreakdown->rows->isNotEmpty() || $this->paymentMethodBreakdown->refund_total != 0)
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Payment Methods') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Method') }}</flux:table.column>
                        <flux:table.column>{{ __('Count') }}</flux:table.column>
                        <flux:table.column>{{ __('Amount') }}</flux:table.column>
                        <flux:table.column>{{ __('% of Total') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->paymentMethodBreakdown->rows as $row)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $row->method->label() }}</flux:table.cell>
                                <flux:table.cell>{{ $row->count }}</flux:table.cell>
                                <flux:table.cell>${{ number_format($row->total, 2) }}</flux:table.cell>
                                <flux:table.cell>{{ $row->percentage }}%</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                        @if ($this->paymentMethodBreakdown->refund_total != 0)
                            <flux:table.row>
                                <flux:table.cell variant="strong" class="text-red-600">{{ __('Refunds') }}</flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                                <flux:table.cell class="text-red-600">${{ number_format(abs($this->paymentMethodBreakdown->refund_total), 2) }}</flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                            </flux:table.row>
                        @endif
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        @if ($this->outstandingInvoices->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Outstanding Invoices') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Student') }}</flux:table.column>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Invoice #') }}</flux:table.column>
                        <flux:table.column>{{ __('Amount Due') }}</flux:table.column>
                        <flux:table.column>{{ __('Paid') }}</flux:table.column>
                        <flux:table.column>{{ __('Balance') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->outstandingInvoices as $invoice)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $invoice->enrollment->student->name }}</flux:table.cell>
                                <flux:table.cell>{{ $invoice->enrollment->section->course->code }}</flux:table.cell>
                                <flux:table.cell>{{ $invoice->invoice_number }}</flux:table.cell>
                                <flux:table.cell>${{ number_format((float) $invoice->amount_due, 2) }}</flux:table.cell>
                                <flux:table.cell>${{ number_format((float) $invoice->totalPaid(), 2) }}</flux:table.cell>
                                <flux:table.cell class="font-semibold text-red-600">${{ number_format((float) $invoice->balance(), 2) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        @if ($this->programRevenue->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Revenue by Program') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Program') }}</flux:table.column>
                        <flux:table.column>{{ __('Invoiced') }}</flux:table.column>
                        <flux:table.column>{{ __('Collected') }}</flux:table.column>
                        <flux:table.column>{{ __('Outstanding') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->programRevenue as $row)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $row->program->name }}</flux:table.cell>
                                <flux:table.cell>${{ number_format($row->invoiced, 2) }}</flux:table.cell>
                                <flux:table.cell>${{ number_format($row->collected, 2) }}</flux:table.cell>
                                <flux:table.cell>{{ $row->outstanding > 0 ? '$'.number_format($row->outstanding, 2) : '—' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    @endif
</section>
