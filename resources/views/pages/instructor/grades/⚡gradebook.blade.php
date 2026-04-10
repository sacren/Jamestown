<?php

use App\Concerns\GradeValidationRules;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Section;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gradebook')] class extends Component {
    use GradeValidationRules;

    public Section $section;

    public function mount(Section $section): void
    {
        abort_unless($section->instructor_id === auth()->id(), 403);

        $this->section = $section->load('course', 'term');
    }

    #[Computed]
    public function assessments()
    {
        return Assessment::query()
            ->where('section_id', $this->section->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function gradebook()
    {
        $assessments = $this->assessments;
        $students = $this->getEnrolledStudentsForGrading($this->section);

        $assessmentIds = $assessments->pluck('id');
        $grades = Grade::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->whereIn('enrollment_id', $students->pluck('id'))
            ->get()
            ->groupBy('enrollment_id');

        return $students->map(function ($enrollment) use ($assessments, $grades) {
            $studentGrades = $grades->get($enrollment->id, collect())->keyBy('assessment_id');

            $totalEarned = 0.0;
            $totalPossible = 0.0;
            $scores = [];

            foreach ($assessments as $assessment) {
                $grade = $studentGrades->get($assessment->id);
                $scores[$assessment->id] = $grade;

                if ($grade) {
                    $totalEarned += (float) $grade->score;
                    $totalPossible += (float) $assessment->max_points;
                }
            }

            $percentage = $totalPossible > 0 ? round(($totalEarned / $totalPossible) * 100, 1) : 0.0;

            return (object) [
                'enrollment' => $enrollment,
                'scores' => $scores,
                'total_earned' => $totalEarned,
                'total_possible' => $totalPossible,
                'percentage' => $percentage,
            ];
        });
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Gradebook') }}</flux:heading>
        <flux:subheading>
            {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }}) — {{ $section->term->name }}
        </flux:subheading>
    </div>

    @if ($this->assessments->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No assessments') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Create assessments before viewing the gradebook.') }}</flux:callout.text>
        </flux:callout>
    @elseif ($this->gradebook->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No enrolled students') }}</flux:callout.heading>
            <flux:callout.text>{{ __('There are no students currently enrolled in this section.') }}</flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                @foreach ($this->assessments as $assessment)
                    <flux:table.column>{{ $assessment->title }}<br><span class="text-xs text-zinc-500">/ {{ $assessment->max_points }}</span></flux:table.column>
                @endforeach
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Percentage') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->gradebook as $row)
                    <flux:table.row :key="$row->enrollment->id">
                        <flux:table.cell variant="strong">
                            <div>{{ $row->enrollment->student->name }}</div>
                            <flux:text variant="subtle" class="text-xs">{{ $row->enrollment->student->studentProfile?->student_id_number }}</flux:text>
                        </flux:table.cell>
                        @foreach ($this->assessments as $assessment)
                            @php($grade = $row->scores[$assessment->id] ?? null)
                            <flux:table.cell>{{ $grade ? number_format((float) $grade->score, 2) : '—' }}</flux:table.cell>
                        @endforeach
                        <flux:table.cell>{{ number_format($row->total_earned, 2) }} / {{ number_format($row->total_possible, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            @php($color = $row->percentage >= 90 ? 'green' : ($row->percentage >= 70 ? 'amber' : 'red'))
                            <flux:badge size="sm" :color="$color" inset="top bottom">{{ $row->percentage }}%</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
