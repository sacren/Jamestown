<?php

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Attendance Report')] class extends Component {
    #[Url]
    public string $termId = '';

    #[Url]
    public string $sectionId = '';

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
    public function sections()
    {
        return Section::query()
            ->when($this->termId, fn ($q) => $q->where('term_id', $this->termId))
            ->with('course')
            ->orderBy('course_id')
            ->get();
    }

    private function baseAttendanceQuery()
    {
        return Attendance::query()
            ->when($this->termId, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termId)))
            ->when($this->sectionId, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('section_id', $this->sectionId)));
    }

    #[Computed]
    public function overallStats(): array
    {
        $records = $this->baseAttendanceQuery()->get();
        $total = $records->count();

        $present = $records->where('status', AttendanceStatus::Present)->count();
        $absent = $records->where('status', AttendanceStatus::Absent)->count();
        $late = $records->where('status', AttendanceStatus::Late)->count();
        $excused = $records->where('status', AttendanceStatus::Excused)->count();

        $attendanceRate = $total > 0 ? round(($present + $late) / $total * 100, 1) : 0;
        $absentRate = $total > 0 ? round($absent / $total * 100, 1) : 0;
        $lateRate = $total > 0 ? round($late / $total * 100, 1) : 0;

        return [
            'total' => $total,
            'attendance_rate' => $attendanceRate,
            'absent_rate' => $absentRate,
            'late_rate' => $lateRate,
        ];
    }

    #[Computed]
    public function sectionRates()
    {
        $sections = Section::query()
            ->when($this->termId, fn ($q) => $q->where('term_id', $this->termId))
            ->when($this->sectionId, fn ($q) => $q->where('id', $this->sectionId))
            ->with('course')
            ->get();

        return $sections->map(function ($section) {
            $records = Attendance::query()
                ->whereHas('enrollment', fn ($q) => $q->where('section_id', $section->id))
                ->get();

            $total = $records->count();
            if ($total === 0) {
                return null;
            }

            $present = $records->where('status', AttendanceStatus::Present)->count();
            $late = $records->where('status', AttendanceStatus::Late)->count();
            $absent = $records->where('status', AttendanceStatus::Absent)->count();
            $rate = round(($present + $late) / $total * 100, 1);

            return (object) [
                'section' => $section,
                'total' => $total,
                'rate' => $rate,
                'absent' => $absent,
            ];
        })
            ->filter()
            ->sortBy('rate')
            ->values();
    }

    #[Computed]
    public function atRiskStudents()
    {
        $enrollments = \App\Models\Enrollment::query()
            ->where('status', EnrollmentStatus::Enrolled)
            ->when($this->termId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('term_id', $this->termId)))
            ->when($this->sectionId, fn ($q) => $q->where('section_id', $this->sectionId))
            ->with(['student.studentProfile', 'section.course'])
            ->get();

        return $enrollments->map(function ($enrollment) {
            $records = $enrollment->attendances;
            $total = $records->count();
            if ($total === 0) {
                return null;
            }

            $present = $records->where('status', AttendanceStatus::Present)->count();
            $late = $records->where('status', AttendanceStatus::Late)->count();
            $absent = $records->where('status', AttendanceStatus::Absent)->count();
            $rate = round(($present + $late) / $total * 100, 1);

            if ($rate >= 75) {
                return null;
            }

            return (object) [
                'enrollment' => $enrollment,
                'rate' => $rate,
                'absent' => $absent,
                'total' => $total,
            ];
        })
            ->filter()
            ->sortBy('rate')
            ->values();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-sm">
            <a href="{{ route('admin.reports.index') }}" wire:navigate class="text-blue-500 hover:underline">{{ __('Reports') }}</a>
            <span class="text-zinc-400">/</span>
            <span>{{ __('Attendance Report') }}</span>
        </div>
        <flux:heading size="xl">{{ __('Attendance Report') }}</flux:heading>
        <flux:subheading>{{ __('Attendance rates, trends, and at-risk students') }}</flux:subheading>
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
            <flux:select wire:model.live="sectionId" placeholder="{{ __('All Sections') }}">
                <flux:select.option value="">{{ __('All Sections') }}</flux:select.option>
                @foreach ($this->sections as $section)
                    <flux:select.option value="{{ $section->id }}">{{ $section->course->code }}-{{ $section->section_number }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Total Records') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['total'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Attendance Rate') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['attendance_rate'] }}%</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Absent Rate') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['absent_rate'] }}%</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Late Rate') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['late_rate'] }}%</flux:heading>
        </div>
    </div>

    @if ($this->overallStats['total'] === 0)
        <flux:callout>
            <flux:callout.heading>{{ __('No attendance records') }}</flux:callout.heading>
            <flux:callout.text>{{ __('No attendance data matches the selected filters.') }}</flux:callout.text>
        </flux:callout>
    @else
        @if ($this->sectionRates->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('By Section') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Section') }}</flux:table.column>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Records') }}</flux:table.column>
                        <flux:table.column>{{ __('Absences') }}</flux:table.column>
                        <flux:table.column>{{ __('Attendance Rate') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->sectionRates as $row)
                            @php
                                $color = $row->rate >= 90 ? 'green' : ($row->rate >= 75 ? 'amber' : 'red');
                            @endphp
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $row->section->displayCode() }}</flux:table.cell>
                                <flux:table.cell>{{ $row->section->course->name }}</flux:table.cell>
                                <flux:table.cell>{{ $row->total }}</flux:table.cell>
                                <flux:table.cell>{{ $row->absent }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$color" inset="top bottom">{{ $row->rate }}%</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        <div class="mb-6">
            <flux:heading size="lg" class="mb-3">{{ __('At-Risk Students') }}</flux:heading>
            @if ($this->atRiskStudents->isEmpty())
                <flux:callout variant="info" icon="check-circle">
                    <flux:callout.text>{{ __('No at-risk students. All students have an attendance rate of 75% or above.') }}</flux:callout.text>
                </flux:callout>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Student') }}</flux:table.column>
                        <flux:table.column>{{ __('Section') }}</flux:table.column>
                        <flux:table.column>{{ __('Absences') }}</flux:table.column>
                        <flux:table.column>{{ __('Attendance Rate') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->atRiskStudents as $row)
                            <flux:table.row>
                                <flux:table.cell variant="strong">
                                    <div>{{ $row->enrollment->student->name }}</div>
                                    <flux:text variant="subtle" class="text-xs">{{ $row->enrollment->student->studentProfile?->student_id_number }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>{{ $row->enrollment->section->course->code }}-{{ $row->enrollment->section->section_number }}</flux:table.cell>
                                <flux:table.cell>{{ $row->absent }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" color="red" inset="top bottom">{{ $row->rate }}%</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    @endif
</section>
