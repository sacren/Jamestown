<?php

use App\Concerns\EnrollmentValidationRules;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Course Registration')] class extends Component {
    use EnrollmentValidationRules, WithPagination;

    #[Url]
    public string $termFilter = '';

    #[Url]
    public string $programFilter = '';

    #[Url]
    public string $search = '';

    /** @var array<string, string> */
    public array $eligibilityErrors = [];

    public function mount(): void
    {
        $openTerm = $this->availableTerms->first();
        if ($openTerm && $this->termFilter === '') {
            $this->termFilter = (string) $openTerm->id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTermFilter(): void
    {
        $this->resetPage();
        $this->eligibilityErrors = [];
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function enroll(int $sectionId): void
    {
        $this->eligibilityErrors = [];

        $student = auth()->user();
        $section = Section::with(['course.prerequisites', 'schedules', 'term'])->findOrFail($sectionId);

        // Check registration period
        $now = now();
        if ($now->lt($section->term->registration_start) || $now->gt($section->term->registration_end)) {
            $this->eligibilityErrors['section_id'] = 'Registration is not currently open for this term.';
            $this->addError('section_id', $this->eligibilityErrors['section_id']);

            return;
        }

        $this->eligibilityErrors = $this->validateEnrollmentEligibility($student, $section);

        if (! empty($this->eligibilityErrors)) {
            foreach ($this->eligibilityErrors as $field => $message) {
                $this->addError($field, $message);
            }

            return;
        }

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
        ]);

        session()->flash('success', 'Successfully enrolled in '.$section->course->code.'-'.$section->section_number.'.');

        unset($this->enrolledSectionIds, $this->sections);
    }

    #[Computed]
    public function availableTerms()
    {
        return Term::query()
            ->where('registration_start', '<=', now())
            ->where('registration_end', '>=', now())
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get();
    }

    #[Computed]
    public function sections()
    {
        if (! $this->termFilter) {
            return collect();
        }

        return Section::query()
            ->where('is_active', true)
            ->where('term_id', $this->termFilter)
            ->with(['course.program', 'course.prerequisites', 'instructor', 'schedules.room'])
            ->withCount(['enrollments' => fn ($q) => $q->where('status', EnrollmentStatus::Enrolled)])
            ->when($this->programFilter, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('program_id', $this->programFilter)))
            ->when($this->search, function ($query) {
                $query->whereHas('course', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->paginate(15);
    }

    #[Computed]
    public function enrolledSectionIds()
    {
        if (! $this->termFilter) {
            return collect();
        }

        return Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('status', EnrollmentStatus::Enrolled)
            ->whereHas('section', fn ($q) => $q->where('term_id', $this->termFilter))
            ->pluck('section_id');
    }

    #[Computed]
    public function programs()
    {
        return Program::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedTerm()
    {
        if (! $this->termFilter) {
            return null;
        }

        return Term::find($this->termFilter);
    }

    /**
     * Check if a student has completed a specific prerequisite course.
     */
    public function hasCompletedCourse(int $courseId): bool
    {
        return Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('status', EnrollmentStatus::Completed)
            ->whereHas('section', fn ($q) => $q->where('course_id', $courseId))
            ->exists();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Course Registration') }}</flux:heading>
        <flux:subheading>{{ __('Browse and enroll in available course sections') }}</flux:subheading>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            <flux:callout.text>{{ session('success') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if (! empty($eligibilityErrors))
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('Enrollment cannot be completed') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-inside list-disc">
                    @foreach ($eligibilityErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    @if ($this->selectedTerm)
        <flux:callout variant="info" icon="information-circle" class="mb-6">
            <flux:callout.text>
                {{ __('Registration for :term is open from :start to :end.', [
                    'term' => $this->selectedTerm->name,
                    'start' => $this->selectedTerm->registration_start->format('M j, Y'),
                    'end' => $this->selectedTerm->registration_end->format('M j, Y'),
                ]) }}
            </flux:callout.text>
        </flux:callout>
    @endif

    @if ($this->availableTerms->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.text>{{ __('There are no terms with open registration at this time.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('Select Term') }}">
                <flux:select.option value="">{{ __('Select Term') }}</flux:select.option>
                @foreach ($this->availableTerms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="programFilter" placeholder="{{ __('All Programs') }}">
                <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
                @foreach ($this->programs as $program)
                    <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search courses...') }}" icon="magnifying-glass" />
        </div>
    </div>

    @if ($this->termFilter)
        <flux:table :paginate="$this->sections">
            <flux:table.columns>
                <flux:table.column>{{ __('Course') }}</flux:table.column>
                <flux:table.column>{{ __('Section') }}</flux:table.column>
                <flux:table.column>{{ __('Instructor') }}</flux:table.column>
                <flux:table.column>{{ __('Schedule') }}</flux:table.column>
                <flux:table.column>{{ __('Seats') }}</flux:table.column>
                <flux:table.column>{{ __('Prerequisites') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->sections as $section)
                    <flux:table.row :key="$section->id">
                        <flux:table.cell variant="strong">
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $section->course->code }}</flux:badge>
                            <span class="ml-1">{{ $section->course->name }}</span>
                            <flux:text variant="subtle" class="text-xs">{{ $section->course->credit_hours }} {{ __('credits') }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $section->section_number }}</flux:table.cell>
                        <flux:table.cell>{{ $section->instructor?->name ?? __('TBA') }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $section->scheduleSummary() }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $isFull = $section->enrollments_count >= $section->max_enrollment;
                            @endphp
                            <span class="{{ $isFull ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                                {{ $section->enrollments_count }}/{{ $section->max_enrollment }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($section->course->prerequisites->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($section->course->prerequisites as $prereq)
                                        @php
                                            $met = $this->hasCompletedCourse($prereq->id);
                                        @endphp
                                        <flux:badge size="sm" :color="$met ? 'green' : 'amber'" inset="top bottom">
                                            {{ $prereq->code }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            @else
                                <flux:text variant="subtle">{{ __('None') }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                @if ($this->enrolledSectionIds->contains($section->id))
                                    <flux:badge color="green" size="sm">{{ __('Enrolled') }}</flux:badge>
                                @elseif ($isFull)
                                    <flux:badge color="zinc" size="sm">{{ __('Full') }}</flux:badge>
                                @else
                                    <flux:button variant="primary" size="sm" wire:click="enroll({{ $section->id }})" wire:confirm="{{ __('Are you sure you want to enroll in this section?') }}">
                                        {{ __('Enroll') }}
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
