<?php

use App\Concerns\GradeValidationRules;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Section;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage Grades')] class extends Component {
    use GradeValidationRules;

    public Section $section;

    public string $assessmentId = '';

    /** @var array<int, array{score: string, notes: string}> */
    public array $grades = [];

    /** @var array<int, array<int, string>> */
    public array $scoreErrors = [];

    public function mount(Section $section): void
    {
        $this->section = $section->load('course', 'term', 'instructor');
    }

    public function updatedAssessmentId(): void
    {
        $this->loadGrades();
    }

    public function loadGrades(): void
    {
        $this->grades = [];
        $this->scoreErrors = [];

        if (! $this->assessmentId) {
            return;
        }

        $students = $this->getEnrolledStudentsForGrading($this->section);
        $existing = Grade::query()
            ->where('assessment_id', $this->assessmentId)
            ->get()
            ->keyBy('enrollment_id');

        foreach ($students as $enrollment) {
            $record = $existing->get($enrollment->id);
            $this->grades[$enrollment->id] = [
                'score' => $record ? (string) $record->score : '',
                'notes' => $record?->notes ?? '',
            ];
        }
    }

    public function saveGrades(): void
    {
        $this->scoreErrors = [];

        if (! $this->assessmentId) {
            return;
        }

        $assessment = Assessment::query()
            ->where('section_id', $this->section->id)
            ->findOrFail($this->assessmentId);

        $hasErrors = false;
        foreach ($this->grades as $enrollmentId => $data) {
            if ($data['score'] === '' || $data['score'] === null) {
                continue;
            }

            $errors = $this->validateScore($assessment, (float) $data['score']);
            if (! empty($errors)) {
                $this->scoreErrors[$enrollmentId] = $errors;
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            return;
        }

        foreach ($this->grades as $enrollmentId => $data) {
            if ($data['score'] === '' || $data['score'] === null) {
                continue;
            }

            Grade::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'assessment_id' => $assessment->id,
                ],
                [
                    'score' => (float) $data['score'],
                    'notes' => $data['notes'] ?: null,
                    'graded_by' => auth()->id(),
                ],
            );
        }

        session()->flash('status', __('Grades saved.'));
    }

    public function assessments()
    {
        return Assessment::query()
            ->where('section_id', $this->section->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function students()
    {
        return $this->getEnrolledStudentsForGrading($this->section);
    }

    public function selectedAssessment(): ?Assessment
    {
        if (! $this->assessmentId) {
            return null;
        }

        return Assessment::query()
            ->where('section_id', $this->section->id)
            ->find($this->assessmentId);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.grades.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Grades') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Manage Grades') }}</flux:heading>
        <flux:subheading>
            {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }}) — {{ $section->term->name }}
            @if ($section->instructor)
                <span class="ml-1">· {{ $section->instructor->name }}</span>
            @endif
        </flux:subheading>
    </div>

    @if (session('status'))
        <flux:callout variant="success" class="mb-4">
            <flux:callout.heading>{{ session('status') }}</flux:callout.heading>
        </flux:callout>
    @endif

    <div class="mb-4 w-full sm:w-96">
        <flux:select wire:model.live="assessmentId" :label="__('Assessment')">
            <flux:select.option value="">{{ __('Select an assessment...') }}</flux:select.option>
            @foreach ($this->assessments() as $assessment)
                <flux:select.option value="{{ $assessment->id }}">
                    {{ $assessment->title }} ({{ $assessment->max_points }} pts)
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @php($selected = $this->selectedAssessment())

    @if (! $selected)
        <flux:callout>
            <flux:callout.heading>{{ __('No assessment selected') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Select an assessment above to manage grades.') }}</flux:callout.text>
        </flux:callout>
    @elseif ($this->students()->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No enrolled students') }}</flux:callout.heading>
            <flux:callout.text>{{ __('There are no students currently enrolled in this section.') }}</flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('Score') }} / {{ $selected->max_points }}</flux:table.column>
                <flux:table.column>{{ __('Notes') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->students() as $enrollment)
                    <flux:table.row :key="$enrollment->id">
                        <flux:table.cell variant="strong">
                            <div>{{ $enrollment->student->name }}</div>
                            <flux:text variant="subtle" class="text-xs">{{ $enrollment->student->studentProfile?->student_id_number }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:input type="number" step="0.01" min="0" :max="$selected->max_points" wire:model="grades.{{ $enrollment->id }}.score" />
                            @if (! empty($scoreErrors[$enrollment->id] ?? []))
                                <div class="mt-1 text-xs text-red-600">
                                    @foreach ($scoreErrors[$enrollment->id] as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:input wire:model="grades.{{ $enrollment->id }}.notes" placeholder="{{ __('Optional notes') }}" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4 flex justify-end">
            <flux:button variant="primary" wire:click="saveGrades">
                {{ __('Save Grades') }}
            </flux:button>
        </div>
    @endif
</section>
