<?php

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Certificates')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $programFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('certificates.view-any'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function certificates()
    {
        return Certificate::query()
            ->with(['student', 'program', 'issuer'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('certificate_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('student', fn ($s) => $s->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->statusFilter, function ($query) {
                match ($this->statusFilter) {
                    CertificateStatus::Active->value => $query->whereNull('revoked_at'),
                    CertificateStatus::Revoked->value => $query->whereNotNull('revoked_at'),
                    default => null,
                };
            })
            ->when($this->programFilter, fn ($q) => $q->where('program_id', $this->programFilter))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function programs()
    {
        return Program::query()->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Certificates') }}</flux:heading>
            <flux:subheading>{{ __('Manage student certificates') }}</flux:subheading>
        </div>
        @can('certificates.manage')
        <flux:button variant="primary" :href="route('admin.certificates.issue')" wire:navigate icon="plus">
            {{ __('Issue Certificate') }}
        </flux:button>
        @endcan
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by certificate # or student name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach (CertificateStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="programFilter" placeholder="{{ __('All Programs') }}">
                <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
                @foreach ($this->programs as $program)
                    <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($this->certificates->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle">{{ __('No certificates found') }}</flux:text>
        </div>
    @else
        <flux:table :paginate="$this->certificates">
            <flux:table.columns>
                <flux:table.column>{{ __('Certificate #') }}</flux:table.column>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('Program') }}</flux:table.column>
                <flux:table.column>{{ __('Issued') }}</flux:table.column>
                <flux:table.column>{{ __('Issued By') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->certificates as $certificate)
                    <flux:table.row :key="$certificate->id">
                        <flux:table.cell variant="strong">{{ $certificate->certificate_number }}</flux:table.cell>
                        <flux:table.cell>{{ $certificate->student->name }}</flux:table.cell>
                        <flux:table.cell>{{ $certificate->program->name }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $certificate->issued_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $certificate->issuer?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$certificate->status()->color()" inset="top bottom">
                                {{ $certificate->status()->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button size="sm" :href="route('admin.certificates.show', $certificate)" wire:navigate>
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
