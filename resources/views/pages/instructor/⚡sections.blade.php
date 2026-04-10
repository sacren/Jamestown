<?php

use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('My Sections')] class extends Component {
    #[Url]
    public string $termFilter = '';

    #[Computed]
    public function sections()
    {
        return Section::query()
            ->where('instructor_id', auth()->id())
            ->with(['course.program', 'term', 'schedules.room'])
            ->when($this->termFilter, fn ($q) => $q->where('term_id', $this->termFilter))
            ->orderBy('term_id', 'desc')
            ->get();
    }

    #[Computed]
    public function terms()
    {
        return Term::query()
            ->whereHas('sections', fn ($q) => $q->where('instructor_id', auth()->id()))
            ->orderBy('start_date', 'desc')
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Sections') }}</flux:heading>
        <flux:subheading>{{ __('Sections you are teaching') }}</flux:subheading>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="w-full sm:w-60">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('All Terms') }}">
                <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($this->sections->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No sections') }}</flux:callout.heading>
            <flux:callout.text>{{ __('You are not currently assigned to any sections.') }}</flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Course') }}</flux:table.column>
                <flux:table.column>{{ __('Section') }}</flux:table.column>
                <flux:table.column>{{ __('Term') }}</flux:table.column>
                <flux:table.column>{{ __('Schedule') }}</flux:table.column>
                <flux:table.column>{{ __('Enrolled') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->sections as $section)
                    <flux:table.row :key="$section->id">
                        <flux:table.cell variant="strong">
                            <div>{{ $section->course->name }}</div>
                            <flux:text variant="subtle" class="text-xs">{{ $section->course->code }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $section->section_number }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $section->term->name }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $section->scheduleSummary() }}</flux:table.cell>
                        <flux:table.cell>{{ $section->currentEnrollmentCount() }}/{{ $section->max_enrollment }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:button size="sm" :href="route('instructor.attendance.record', $section)" wire:navigate>
                                    {{ __('Record Attendance') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" :href="route('instructor.attendance.summary', $section)" wire:navigate>
                                    {{ __('Summary') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" :href="route('instructor.assessments.index', $section)" wire:navigate>
                                    {{ __('Assessments') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" :href="route('instructor.grades.entry', $section)" wire:navigate>
                                    {{ __('Grades') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" :href="route('instructor.grades.gradebook', $section)" wire:navigate>
                                    {{ __('Gradebook') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
