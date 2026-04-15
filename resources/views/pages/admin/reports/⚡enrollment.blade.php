<?php

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Enrollment Report')] class extends Component {
    #[Url]
    public string $termId = '';

    #[Url]
    public string $programId = '';

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

    #[Computed]
    public function programs()
    {
        return Program::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedTerm(): ?Term
    {
        return $this->termId ? Term::find($this->termId) : null;
    }

    private function baseEnrollmentQuery()
    {
        return Enrollment::query()
            ->when($this->termId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('term_id', $this->termId)))
            ->when($this->programId, fn ($q) => $q->whereHas('section.course', fn ($c) => $c->where('program_id', $this->programId)));
    }

    #[Computed]
    public function stats(): array
    {
        $query = $this->baseEnrollmentQuery();

        return [
            'total' => (clone $query)->count(),
            'enrolled' => (clone $query)->where('status', EnrollmentStatus::Enrolled)->count(),
            'completed' => (clone $query)->where('status', EnrollmentStatus::Completed)->count(),
            'dropped' => (clone $query)->where('status', EnrollmentStatus::Dropped)->count(),
            'withdrawn' => (clone $query)->where('status', EnrollmentStatus::Withdrawn)->count(),
        ];
    }

    #[Computed]
    public function enrollmentsByProgram()
    {
        $enrollments = $this->baseEnrollmentQuery()
            ->with('section.course.program')
            ->get();

        return $enrollments
            ->groupBy(fn ($e) => $e->section->course->program_id)
            ->map(function ($group) {
                $program = $group->first()->section->course->program;

                return (object) [
                    'program' => $program,
                    'total' => $group->count(),
                    'enrolled' => $group->where('status', EnrollmentStatus::Enrolled)->count(),
                    'completed' => $group->where('status', EnrollmentStatus::Completed)->count(),
                    'dropped' => $group->where('status', EnrollmentStatus::Dropped)->count(),
                    'withdrawn' => $group->where('status', EnrollmentStatus::Withdrawn)->count(),
                ];
            })
            ->sortBy(fn ($row) => $row->program->name)
            ->values();
    }

    #[Computed]
    public function enrollmentsBySection()
    {
        $sections = Section::query()
            ->when($this->termId, fn ($q) => $q->where('term_id', $this->termId))
            ->when($this->programId, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('program_id', $this->programId)))
            ->with(['course', 'instructor'])
            ->withCount([
                'enrollments as enrolled_count' => fn ($q) => $q->where('status', EnrollmentStatus::Enrolled),
                'enrollments as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed),
                'enrollments as total_enrollment_count',
            ])
            ->get()
            ->filter(fn ($s) => $s->total_enrollment_count > 0)
            ->sortByDesc('total_enrollment_count')
            ->values();

        return $sections->map(function ($section) {
            $denominator = $section->enrolled_count + $section->completed_count;
            $completionRate = $denominator > 0
                ? round($section->completed_count / $denominator * 100, 1)
                : 0;

            return (object) [
                'section' => $section,
                'enrolled_count' => $section->enrolled_count,
                'completed_count' => $section->completed_count,
                'total_enrollment_count' => $section->total_enrollment_count,
                'completion_rate' => $completionRate,
            ];
        });
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-sm">
            <a href="{{ route('admin.reports.index') }}" wire:navigate class="text-blue-500 hover:underline">{{ __('Reports') }}</a>
            <span class="text-zinc-400">/</span>
            <span>{{ __('Enrollment Report') }}</span>
        </div>
        <flux:heading size="xl">{{ __('Enrollment Report') }}</flux:heading>
        <flux:subheading>{{ __('Enrollment statistics by term, program, and section') }}</flux:subheading>
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
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="programId" placeholder="{{ __('All Programs') }}">
                <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
                @foreach ($this->programs as $program)
                    <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Enrollments') }}</flux:text>
            <flux:heading size="lg">{{ $this->stats['total'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Enrolled') }}</flux:text>
            <flux:heading size="lg" class="text-blue-600">{{ $this->stats['enrolled'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Completed') }}</flux:text>
            <flux:heading size="lg" class="text-green-600">{{ $this->stats['completed'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Dropped') }}</flux:text>
            <flux:heading size="lg" class="text-zinc-500">{{ $this->stats['dropped'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Withdrawn') }}</flux:text>
            <flux:heading size="lg" class="text-amber-600">{{ $this->stats['withdrawn'] }}</flux:heading>
        </div>
    </div>

    @if ($this->stats['total'] === 0)
        <flux:callout>
            <flux:callout.heading>{{ __('No enrollments found') }}</flux:callout.heading>
            <flux:callout.text>{{ __('No enrollment data matches the selected filters.') }}</flux:callout.text>
        </flux:callout>
    @else
        @if ($this->enrollmentsByProgram->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('By Program') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Program') }}</flux:table.column>
                        <flux:table.column>{{ __('Total') }}</flux:table.column>
                        <flux:table.column>{{ __('Enrolled') }}</flux:table.column>
                        <flux:table.column>{{ __('Completed') }}</flux:table.column>
                        <flux:table.column>{{ __('Dropped') }}</flux:table.column>
                        <flux:table.column>{{ __('Withdrawn') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->enrollmentsByProgram as $row)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $row->program->name }}</flux:table.cell>
                                <flux:table.cell>{{ $row->total }}</flux:table.cell>
                                <flux:table.cell>{{ $row->enrolled }}</flux:table.cell>
                                <flux:table.cell>{{ $row->completed }}</flux:table.cell>
                                <flux:table.cell>{{ $row->dropped }}</flux:table.cell>
                                <flux:table.cell>{{ $row->withdrawn }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        @if ($this->enrollmentsBySection->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('By Section') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Section') }}</flux:table.column>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Instructor') }}</flux:table.column>
                        <flux:table.column>{{ __('Enrolled / Capacity') }}</flux:table.column>
                        <flux:table.column>{{ __('Completion Rate') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->enrollmentsBySection as $row)
                            @php
                                $color = $row->completion_rate >= 70 ? 'green' : ($row->completion_rate >= 50 ? 'amber' : 'zinc');
                            @endphp
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $row->section->displayCode() }}</flux:table.cell>
                                <flux:table.cell>{{ $row->section->course->name }}</flux:table.cell>
                                <flux:table.cell>{{ $row->section->instructor?->name ?? __('TBA') }}</flux:table.cell>
                                <flux:table.cell>{{ $row->enrolled_count }} / {{ $row->section->max_enrollment }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$color" inset="top bottom">{{ $row->completion_rate }}%</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    @endif
</section>
