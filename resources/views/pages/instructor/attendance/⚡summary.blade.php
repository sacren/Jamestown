<?php

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Attendance;
use App\Models\Section;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Attendance Summary')] class extends Component {
    public Section $section;

    public function mount(Section $section): void
    {
        abort_unless($section->instructor_id === auth()->id(), 403);

        $this->section = $section->load('course', 'term');
    }

    #[Computed]
    public function students()
    {
        $enrollments = $this->section->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('student.studentProfile')
            ->get();

        return $enrollments->map(function ($enrollment) {
            $records = Attendance::query()
                ->where('enrollment_id', $enrollment->id)
                ->get();

            $total = $records->count();
            $present = $records->where('status', AttendanceStatus::Present)->count();
            $absent = $records->where('status', AttendanceStatus::Absent)->count();
            $late = $records->where('status', AttendanceStatus::Late)->count();
            $excused = $records->where('status', AttendanceStatus::Excused)->count();

            $rate = $total > 0
                ? round((($present + $late) / $total) * 100, 1)
                : 0;

            return (object) [
                'enrollment' => $enrollment,
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'rate' => $rate,
            ];
        });
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Attendance Summary') }}</flux:heading>
        <flux:subheading>
            {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }}) — {{ $section->term->name }}
        </flux:subheading>
    </div>

    @if ($this->students->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No enrolled students') }}</flux:callout.heading>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Present') }}</flux:table.column>
                <flux:table.column>{{ __('Absent') }}</flux:table.column>
                <flux:table.column>{{ __('Late') }}</flux:table.column>
                <flux:table.column>{{ __('Excused') }}</flux:table.column>
                <flux:table.column>{{ __('Rate') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->students as $row)
                    @php
                        $color = $row->total === 0 ? 'zinc' : ($row->rate >= 90 ? 'green' : ($row->rate >= 75 ? 'amber' : 'red'));
                    @endphp
                    <flux:table.row :key="$row->enrollment->id">
                        <flux:table.cell variant="strong">
                            <div>{{ $row->enrollment->student->name }}</div>
                            <flux:text variant="subtle" class="text-xs">{{ $row->enrollment->student->studentProfile?->student_id_number }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $row->total }}</flux:table.cell>
                        <flux:table.cell>{{ $row->present }}</flux:table.cell>
                        <flux:table.cell>{{ $row->absent }}</flux:table.cell>
                        <flux:table.cell>{{ $row->late }}</flux:table.cell>
                        <flux:table.cell>{{ $row->excused }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$color" inset="top bottom">
                                {{ $row->rate }}%
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
