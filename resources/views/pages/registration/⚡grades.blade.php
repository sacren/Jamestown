<?php

use App\Enums\EnrollmentStatus;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('My Grades')] class extends Component {
    #[Url]
    public string $termFilter = '';

    #[Url]
    public string $sectionFilter = '';

    #[Computed]
    public function grades()
    {
        return Grade::query()
            ->whereHas('enrollment', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['assessment', 'enrollment.section.course', 'enrollment.section.term'])
            ->when($this->termFilter, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termFilter)))
            ->when($this->sectionFilter, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('section_id', $this->sectionFilter)))
            ->get()
            ->sortBy([
                fn ($a, $b) => $a->enrollment->section_id <=> $b->enrollment->section_id,
                fn ($a, $b) => $a->assessment->sort_order <=> $b->assessment->sort_order,
            ])
            ->values();
    }

    #[Computed]
    public function summary()
    {
        return $this->grades
            ->groupBy(fn ($g) => $g->enrollment->section_id)
            ->map(function ($records) {
                $totalEarned = 0.0;
                $totalPossible = 0.0;

                foreach ($records as $grade) {
                    $totalEarned += (float) $grade->score;
                    $totalPossible += (float) $grade->assessment->max_points;
                }

                $percentage = $totalPossible > 0 ? round(($totalEarned / $totalPossible) * 100, 1) : 0.0;

                return (object) [
                    'section' => $records->first()->enrollment->section,
                    'total_earned' => $totalEarned,
                    'total_possible' => $totalPossible,
                    'percentage' => $percentage,
                    'count' => $records->count(),
                ];
            })
            ->values();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', auth()->id())->where('status', EnrollmentStatus::Enrolled))
            ->with('course', 'term')
            ->get();
    }

    #[Computed]
    public function terms()
    {
        return Term::query()
            ->whereHas('sections.enrollments', fn ($q) => $q->where('user_id', auth()->id()))
            ->orderBy('start_date', 'desc')
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Grades') }}</flux:heading>
        <flux:subheading>{{ __('Your grades and assessment scores') }}</flux:subheading>
    </div>

    @if ($this->sections->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No enrollments') }}</flux:callout.heading>
            <flux:callout.text>{{ __('You are not currently enrolled in any sections.') }}</flux:callout.text>
        </flux:callout>
    @else
        @if ($this->summary->isNotEmpty())
            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->summary as $row)
                    @php
                        $color = $row->percentage >= 90 ? 'green' : ($row->percentage >= 70 ? 'amber' : 'red');
                    @endphp
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="font-semibold">{{ $row->section->course->code }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $row->section->course->name }}</div>
                        <div class="mt-2 flex items-center justify-between">
                            <flux:badge size="sm" :color="$color">{{ $row->percentage }}%</flux:badge>
                            <span class="text-xs">{{ number_format($row->total_earned, 2) }} / {{ number_format($row->total_possible, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mb-4 flex flex-col gap-4 sm:flex-row">
            <div class="w-full sm:w-44">
                <flux:select wire:model.live="termFilter" placeholder="{{ __('All Terms') }}">
                    <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                    @foreach ($this->terms as $term)
                        <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-full sm:w-60">
                <flux:select wire:model.live="sectionFilter" placeholder="{{ __('All Sections') }}">
                    <flux:select.option value="">{{ __('All Sections') }}</flux:select.option>
                    @foreach ($this->sections as $section)
                        <flux:select.option value="{{ $section->id }}">{{ $section->course->code }} - {{ $section->section_number }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if ($this->grades->isEmpty())
            <flux:callout>
                <flux:callout.heading>{{ __('No grades yet') }}</flux:callout.heading>
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Course') }}</flux:table.column>
                    <flux:table.column>{{ __('Assessment') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Score') }}</flux:table.column>
                    <flux:table.column>{{ __('Percentage') }}</flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->grades as $grade)
                        @php
                            $pct = $grade->assessment->max_points > 0 ? round(((float) $grade->score / (float) $grade->assessment->max_points) * 100, 1) : 0;
                            $color = $pct >= 90 ? 'green' : ($pct >= 70 ? 'amber' : 'red');
                        @endphp
                        <flux:table.row :key="$grade->id">
                            <flux:table.cell variant="strong">
                                {{ $grade->enrollment->section->course->code }}
                                <flux:text variant="subtle" class="ml-1 text-xs">{{ $grade->enrollment->section->course->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ $grade->assessment->title }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="blue" inset="top bottom">{{ ucfirst($grade->assessment->type->value) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ number_format((float) $grade->score, 2) }} / {{ $grade->assessment->max_points }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$color" inset="top bottom">{{ $pct }}%</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $grade->notes }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif
</section>
