<?php

use App\Models\Enrollment;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Transcript')] class extends Component {
    #[Computed]
    public function programData()
    {
        $enrollments = Enrollment::query()
            ->where('user_id', auth()->id())
            ->with(['section.course.program', 'section.term', 'grades.assessment'])
            ->get();

        return $enrollments
            ->groupBy(fn ($e) => $e->section->course->program_id)
            ->map(function ($programEnrollments) {
                $program = $programEnrollments->first()->section->course->program;
                $totalCourses = $program->courses()->count();

                $courses = $programEnrollments->map(function ($enrollment) {
                    $totalEarned = 0.0;
                    $totalPossible = 0.0;
                    foreach ($enrollment->grades as $grade) {
                        $totalEarned += (float) $grade->score;
                        $totalPossible += (float) $grade->assessment->max_points;
                    }
                    $gradePercent = $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 1) : null;

                    return (object) [
                        'code' => $enrollment->section->course->code,
                        'name' => $enrollment->section->course->name,
                        'term' => $enrollment->section->term->name,
                        'status' => $enrollment->status,
                        'grade_percent' => $gradePercent,
                        'credit_hours' => $enrollment->section->course->credit_hours,
                        'course_id' => $enrollment->section->course_id,
                    ];
                })->sortBy('code')->values();

                $completedCount = $courses->filter(fn ($c) => $c->status->value === 'completed')->unique('course_id')->count();
                $creditsEarned = $courses->filter(fn ($c) => $c->status->value === 'completed')->unique('course_id')->sum('credit_hours');

                return (object) [
                    'program' => $program,
                    'courses' => $courses,
                    'total_courses' => $totalCourses,
                    'completed_count' => $completedCount,
                    'credits_earned' => $creditsEarned,
                ];
            })
            ->sortBy(fn ($d) => $d->program->name)
            ->values();
    }

    #[Computed]
    public function totalCreditsEarned(): int
    {
        return $this->programData->sum('credits_earned');
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('My Transcript') }}</flux:heading>
        <flux:subheading>{{ __('Your academic record') }}</flux:subheading>
    </div>

    @if ($this->programData->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No enrollments') }}</flux:callout.heading>
            <flux:callout.text>{{ __('You have no enrollment records to display.') }}</flux:callout.text>
        </flux:callout>
    @else
        @foreach ($this->programData as $data)
            <div class="mb-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="sm">{{ $data->program->name }} ({{ $data->program->code }})</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        {{ $data->completed_count }} {{ __('of') }} {{ $data->total_courses }} {{ __('courses completed') }} &middot;
                        {{ $data->credits_earned }} {{ __('credits earned') }}
                    </flux:text>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Term') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Grade') }}</flux:table.column>
                        <flux:table.column>{{ __('Credits') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($data->courses as $course)
                            @php
                                $statusColor = match($course->status->value) {
                                    'completed' => 'green',
                                    'enrolled' => 'blue',
                                    'dropped' => 'zinc',
                                    'withdrawn' => 'amber',
                                };
                            @endphp
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $course->code }}</flux:table.cell>
                                <flux:table.cell>{{ $course->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $course->term }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$statusColor" inset="top bottom">
                                        {{ ucfirst($course->status->value) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $course->grade_percent !== null ? $course->grade_percent.'%' : __('N/A') }}</flux:table.cell>
                                <flux:table.cell>{{ $course->credit_hours }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endforeach

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm">{{ __('Total Credits Earned') }}: {{ $this->totalCreditsEarned }}</flux:heading>
        </div>
    @endif
</section>
