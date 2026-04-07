<?php

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('My Schedule')] class extends Component {
    #[Url]
    public string $termFilter = '';

    public function mount(): void
    {
        if ($this->termFilter === '') {
            $firstTerm = $this->terms->first();
            if ($firstTerm) {
                $this->termFilter = (string) $firstTerm->id;
            }
        }
    }

    public function dropEnrollment(int $enrollmentId): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        abort_unless((int) $enrollment->user_id === (int) auth()->id(), 403);

        $term = $enrollment->section->term;
        $now = now();

        if ($now->lt($term->registration_start) || $now->gt($term->registration_end)) {
            $this->addError('drop', 'Enrollments can only be dropped during the registration period.');

            return;
        }

        $enrollment->update([
            'status' => EnrollmentStatus::Dropped,
            'dropped_at' => now(),
        ]);

        session()->flash('success', 'Successfully dropped '.$enrollment->section->course->code.'-'.$enrollment->section->section_number.'.');

        unset($this->enrollments);
    }

    #[Computed]
    public function enrollments()
    {
        if (! $this->termFilter) {
            return collect();
        }

        return Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('status', EnrollmentStatus::Enrolled)
            ->whereHas('section', fn ($q) => $q->where('term_id', $this->termFilter))
            ->with(['section.course', 'section.schedules.room', 'section.instructor'])
            ->get();
    }

    #[Computed]
    public function terms()
    {
        $termIds = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('status', EnrollmentStatus::Enrolled)
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->distinct()
            ->pluck('sections.term_id');

        return Term::whereIn('id', $termIds)->orderBy('start_date', 'desc')->get();
    }

    #[Computed]
    public function totalCredits()
    {
        return $this->enrollments->sum(fn ($e) => $e->section->course->credit_hours);
    }

    #[Computed]
    public function isRegistrationOpen()
    {
        if (! $this->termFilter) {
            return false;
        }

        $term = Term::find($this->termFilter);
        if (! $term) {
            return false;
        }

        $now = now();

        return $now->gte($term->registration_start) && $now->lte($term->registration_end);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Schedule') }}</flux:heading>
        <flux:subheading>{{ __('View your enrolled course sections') }}</flux:subheading>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            <flux:callout.text>{{ session('success') }}</flux:callout.text>
        </flux:callout>
    @endif

    @error('drop')
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('Select Term') }}">
                <flux:select.option value="">{{ __('Select Term') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if ($this->enrollments->isNotEmpty())
            <flux:text>
                {{ __('Total Credits: :credits', ['credits' => $this->totalCredits]) }}
            </flux:text>
        @endif
    </div>

    @if ($this->enrollments->isEmpty())
        <flux:callout variant="info" icon="information-circle">
            <flux:callout.text>{{ __('No enrolled sections for this term.') }}</flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Course') }}</flux:table.column>
                <flux:table.column>{{ __('Section') }}</flux:table.column>
                <flux:table.column>{{ __('Instructor') }}</flux:table.column>
                <flux:table.column>{{ __('Schedule') }}</flux:table.column>
                <flux:table.column>{{ __('Room') }}</flux:table.column>
                <flux:table.column>{{ __('Credits') }}</flux:table.column>
                @if ($this->isRegistrationOpen)
                    <flux:table.column></flux:table.column>
                @endif
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->enrollments as $enrollment)
                    <flux:table.row :key="$enrollment->id">
                        <flux:table.cell variant="strong">
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $enrollment->section->course->code }}</flux:badge>
                            <span class="ml-1">{{ $enrollment->section->course->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $enrollment->section->section_number }}</flux:table.cell>
                        <flux:table.cell>{{ $enrollment->section->instructor?->name ?? __('TBA') }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $enrollment->section->scheduleSummary() }}</flux:table.cell>
                        <flux:table.cell class="text-sm">
                            {{ $enrollment->section->schedules->map(fn ($s) => $s->room?->name)->filter()->unique()->implode(', ') ?: __('TBA') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $enrollment->section->course->credit_hours }}</flux:table.cell>
                        @if ($this->isRegistrationOpen)
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:button variant="ghost" size="sm" wire:click="dropEnrollment({{ $enrollment->id }})" wire:confirm="{{ __('Are you sure you want to drop this section?') }}">
                                        {{ __('Drop') }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
