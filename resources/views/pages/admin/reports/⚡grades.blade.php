<?php

use App\Models\Course;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Grade Performance Report')] class extends Component {
    #[Url]
    public string $termId = '';

    #[Url]
    public string $courseId = '';

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
    public function courses()
    {
        return Course::query()
            ->when($this->termId, fn ($q) => $q->whereHas('sections', fn ($s) => $s->where('term_id', $this->termId)))
            ->orderBy('code')
            ->get();
    }

    private function baseGradeQuery()
    {
        return Grade::query()
            ->when($this->termId, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termId)))
            ->when($this->courseId, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('course_id', $this->courseId)));
    }

    private function gradePercent(Grade $grade): float
    {
        return $grade->assessment->max_points > 0
            ? round(((float) $grade->score / (float) $grade->assessment->max_points) * 100, 1)
            : 0;
    }

    #[Computed]
    public function overallStats(): array
    {
        $grades = $this->baseGradeQuery()->with('assessment')->get();
        $total = $grades->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'average' => 0,
                'highest_section' => null,
                'lowest_section' => null,
            ];
        }

        $percents = $grades->map(fn ($g) => $this->gradePercent($g));
        $average = round($percents->avg(), 1);

        // Get section averages for highest/lowest
        $sectionData = $this->sectionPerformance;
        $highest = $sectionData->sortByDesc('avg_percent')->first();
        $lowest = $sectionData->sortBy('avg_percent')->first();

        return [
            'total' => $total,
            'average' => $average,
            'highest_section' => $highest,
            'lowest_section' => $lowest,
        ];
    }

    #[Computed]
    public function sectionPerformance()
    {
        $grades = $this->baseGradeQuery()
            ->with(['assessment', 'enrollment.section.course', 'enrollment.section.instructor'])
            ->get();

        return $grades
            ->groupBy(fn ($g) => $g->enrollment->section_id)
            ->map(function ($sectionGrades) {
                $section = $sectionGrades->first()->enrollment->section;
                $percents = $sectionGrades->map(fn ($g) => $this->gradePercent($g));

                return (object) [
                    'section' => $section,
                    'count' => $sectionGrades->count(),
                    'avg_percent' => round($percents->avg(), 1),
                    'highest_percent' => round($percents->max(), 1),
                    'lowest_percent' => round($percents->min(), 1),
                ];
            })
            ->sortByDesc('avg_percent')
            ->values();
    }

    #[Computed]
    public function gradeDistribution(): array
    {
        $grades = $this->baseGradeQuery()->with('assessment')->get();
        $total = $grades->count();

        $buckets = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];

        foreach ($grades as $grade) {
            $pct = $this->gradePercent($grade);
            match (true) {
                $pct >= 90 => $buckets['A']++,
                $pct >= 80 => $buckets['B']++,
                $pct >= 70 => $buckets['C']++,
                $pct >= 60 => $buckets['D']++,
                default => $buckets['F']++,
            };
        }

        return collect($buckets)->map(fn ($count, $letter) => (object) [
            'letter' => $letter,
            'count' => $count,
            'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0,
        ])->values()->all();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-sm">
            <a href="{{ route('admin.reports.index') }}" wire:navigate class="text-blue-500 hover:underline">{{ __('Reports') }}</a>
            <span class="text-zinc-400">/</span>
            <span>{{ __('Grade Performance Report') }}</span>
        </div>
        <flux:heading size="xl">{{ __('Grade Performance Report') }}</flux:heading>
        <flux:subheading>{{ __('Grade distributions, section averages, and academic performance') }}</flux:subheading>
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
            <flux:select wire:model.live="courseId" placeholder="{{ __('All Courses') }}">
                <flux:select.option value="">{{ __('All Courses') }}</flux:select.option>
                @foreach ($this->courses as $course)
                    <flux:select.option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Grades') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['total'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Average Score') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['average'] }}%</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Highest Section Avg') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['highest_section']?->avg_percent ?? 'N/A' }}{{ $this->overallStats['highest_section'] ? '%' : '' }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Lowest Section Avg') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['lowest_section']?->avg_percent ?? 'N/A' }}{{ $this->overallStats['lowest_section'] ? '%' : '' }}</flux:heading>
        </div>
    </div>

    @if ($this->overallStats['total'] === 0)
        <flux:callout>
            <flux:callout.heading>{{ __('No grades recorded') }}</flux:callout.heading>
            <flux:callout.text>{{ __('No grade data matches the selected filters.') }}</flux:callout.text>
        </flux:callout>
    @else
        @if ($this->sectionPerformance->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('By Section') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Section') }}</flux:table.column>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Instructor') }}</flux:table.column>
                        <flux:table.column>{{ __('Grades') }}</flux:table.column>
                        <flux:table.column>{{ __('Avg') }}</flux:table.column>
                        <flux:table.column>{{ __('Highest') }}</flux:table.column>
                        <flux:table.column>{{ __('Lowest') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->sectionPerformance as $row)
                            @php
                                $color = $row->avg_percent >= 80 ? 'green' : ($row->avg_percent >= 60 ? 'amber' : 'red');
                            @endphp
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $row->section->displayCode() }}</flux:table.cell>
                                <flux:table.cell>{{ $row->section->course->name }}</flux:table.cell>
                                <flux:table.cell>{{ $row->section->instructor?->name ?? __('TBA') }}</flux:table.cell>
                                <flux:table.cell>{{ $row->count }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$color" inset="top bottom">{{ $row->avg_percent }}%</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $row->highest_percent }}%</flux:table.cell>
                                <flux:table.cell>{{ $row->lowest_percent }}%</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        <div class="mb-6">
            <flux:heading size="lg" class="mb-3">{{ __('Grade Distribution') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Grade') }}</flux:table.column>
                    <flux:table.column>{{ __('Count') }}</flux:table.column>
                    <flux:table.column>{{ __('Percentage') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->gradeDistribution as $bucket)
                        @php
                            $color = match($bucket->letter) {
                                'A' => 'green',
                                'B' => 'blue',
                                'C' => 'amber',
                                'D' => 'zinc',
                                'F' => 'red',
                            };
                        @endphp
                        <flux:table.row>
                            <flux:table.cell variant="strong">
                                <flux:badge size="sm" :color="$color" inset="top bottom">{{ $bucket->letter }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $bucket->count }}</flux:table.cell>
                            <flux:table.cell>{{ $bucket->percentage }}%</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</section>
