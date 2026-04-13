<?php

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Enrollment Management')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $termFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTermFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function dropEnrollment(int $enrollmentId): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $enrollment->update([
            'status' => EnrollmentStatus::Dropped,
            'dropped_at' => now(),
        ]);

        $this->voidInvoiceIfUnpaid($enrollment);
    }

    public function withdrawEnrollment(int $enrollmentId): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $enrollment->update([
            'status' => EnrollmentStatus::Withdrawn,
            'dropped_at' => now(),
        ]);

        $this->voidInvoiceIfUnpaid($enrollment);
    }

    public function completeEnrollment(int $enrollmentId): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            return;
        }

        $enrollment->update([
            'status' => EnrollmentStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    private function voidInvoiceIfUnpaid(Enrollment $enrollment): void
    {
        $invoice = $enrollment->invoice()->first();
        if ($invoice && ! $invoice->isVoided() && $invoice->payments()->count() === 0) {
            $invoice->update(['voided_at' => now()]);
        }
    }

    #[Computed]
    public function enrollments()
    {
        return Enrollment::query()
            ->with(['student.studentProfile', 'section.course.program', 'section.term'])
            ->when($this->search, function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->termFilter, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('term_id', $this->termFilter)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function terms()
    {
        return Term::query()->orderBy('start_date', 'desc')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Enrollment Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage student enrollments') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.enrollments.create')" wire:navigate icon="plus">
            {{ __('Manual Enroll') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by student name or email...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('All Terms') }}">
                <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach (EnrollmentStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->enrollments">
        <flux:table.columns>
            <flux:table.column>{{ __('Student') }}</flux:table.column>
            <flux:table.column>{{ __('Course / Section') }}</flux:table.column>
            <flux:table.column>{{ __('Term') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Enrolled At') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->enrollments as $enrollment)
                <flux:table.row :key="$enrollment->id">
                    <flux:table.cell variant="strong">
                        <div>{{ $enrollment->student->name }}</div>
                        <flux:text variant="subtle" class="text-xs">{{ $enrollment->student->studentProfile?->student_id_number }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $enrollment->section->course->code }}</flux:badge>
                        <span class="ml-1">{{ $enrollment->section->course->name }} - {{ $enrollment->section->section_number }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $enrollment->section->term->name }}</flux:table.cell>
                    <flux:table.cell>
                        @php
                            $statusColor = match($enrollment->status) {
                                EnrollmentStatus::Enrolled => 'green',
                                EnrollmentStatus::Completed => 'blue',
                                EnrollmentStatus::Withdrawn => 'amber',
                                EnrollmentStatus::Dropped => 'zinc',
                            };
                        @endphp
                        <flux:badge size="sm" :color="$statusColor" inset="top bottom">
                            {{ ucfirst($enrollment->status->value) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $enrollment->enrolled_at->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($enrollment->status === EnrollmentStatus::Enrolled)
                            <div class="flex justify-end gap-2">
                                <flux:button variant="ghost" size="sm" wire:click="dropEnrollment({{ $enrollment->id }})" wire:confirm="{{ __('Are you sure you want to drop this enrollment?') }}">
                                    {{ __('Drop') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="withdrawEnrollment({{ $enrollment->id }})" wire:confirm="{{ __('Are you sure you want to withdraw this student?') }}">
                                    {{ __('Withdraw') }}
                                </flux:button>
                                <flux:button variant="primary" size="sm" wire:click="completeEnrollment({{ $enrollment->id }})" wire:confirm="{{ __('Are you sure you want to mark this enrollment as completed?') }}">
                                    {{ __('Complete') }}
                                </flux:button>
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
