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

new #[Title('My Attendance')] class extends Component {
    #[Url]
    public string $termFilter = '';

    #[Url]
    public string $sectionFilter = '';

    #[Computed]
    public function attendanceRecords()
    {
        return Attendance::query()
            ->whereHas('enrollment', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['enrollment.section.course', 'enrollment.section.term', 'sectionSchedule'])
            ->when($this->termFilter, fn ($q) => $q->whereHas('enrollment.section', fn ($s) => $s->where('term_id', $this->termFilter)))
            ->when($this->sectionFilter, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('section_id', $this->sectionFilter)))
            ->orderBy('date', 'desc')
            ->get();
    }

    #[Computed]
    public function summary()
    {
        return $this->attendanceRecords
            ->groupBy(fn ($a) => $a->enrollment->section_id)
            ->map(function ($records) {
                $total = $records->count();
                $present = $records->where('status', AttendanceStatus::Present)->count();
                $late = $records->where('status', AttendanceStatus::Late)->count();
                $absent = $records->where('status', AttendanceStatus::Absent)->count();
                $rate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;

                return (object) [
                    'section' => $records->first()->enrollment->section,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'rate' => $rate,
                ];
            });
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
        <flux:heading size="xl">{{ __('My Attendance') }}</flux:heading>
        <flux:subheading>{{ __('Your attendance records') }}</flux:subheading>
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
                        $color = $row->rate >= 90 ? 'green' : ($row->rate >= 75 ? 'amber' : 'red');
                    @endphp
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="font-semibold">{{ $row->section->course->code }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $row->section->course->name }}</div>
                        <div class="mt-2 flex items-center justify-between">
                            <flux:badge size="sm" :color="$color">{{ $row->rate }}%</flux:badge>
                            <span class="text-xs">{{ $row->total }} {{ __('classes') }} • {{ $row->absent }} {{ __('absences') }}</span>
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

        @if ($this->attendanceRecords->isEmpty())
            <flux:callout>
                <flux:callout.heading>{{ __('No attendance records') }}</flux:callout.heading>
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Course') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->attendanceRecords as $record)
                        @php
                            $color = match($record->status) {
                                AttendanceStatus::Present => 'green',
                                AttendanceStatus::Late => 'amber',
                                AttendanceStatus::Absent => 'red',
                                AttendanceStatus::Excused => 'blue',
                            };
                        @endphp
                        <flux:table.row :key="$record->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $record->date->format('M j, Y') }}</flux:table.cell>
                            <flux:table.cell variant="strong">
                                {{ $record->enrollment->section->course->code }}
                                <flux:text variant="subtle" class="ml-1 text-xs">{{ $record->enrollment->section->course->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$color" inset="top bottom">{{ ucfirst($record->status->value) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $record->notes }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif
</section>
