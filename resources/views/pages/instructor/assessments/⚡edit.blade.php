<?php

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Section;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Assessment')] class extends Component {
    public Section $section;
    public Assessment $assessment;

    public string $title = '';
    public string $type = 'assignment';
    public string $description = '';
    public ?int $max_points = 100;
    public string $due_date = '';
    public int $sort_order = 0;

    public function mount(Section $section, Assessment $assessment): void
    {
        abort_unless($section->instructor_id === auth()->id(), 403);
        abort_unless($assessment->section_id === $section->id, 404);

        $this->section = $section->load('course', 'term');
        $this->assessment = $assessment;

        $this->title = $assessment->title;
        $this->type = $assessment->type->value;
        $this->description = $assessment->description ?? '';
        $this->max_points = $assessment->max_points;
        $this->due_date = $assessment->due_date?->toDateString() ?? '';
        $this->sort_order = $assessment->sort_order;
    }

    public function updateAssessment(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column(AssessmentType::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'max_points' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $this->assessment->update([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?: null,
            'max_points' => $validated['max_points'],
            'due_date' => $validated['due_date'] ?: null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->redirect(route('instructor.assessments.index', $this->section), navigate: true);
    }

    public function deleteAssessment(): void
    {
        if ($this->assessment->grades()->exists()) {
            session()->flash('error', __('Cannot delete an assessment that has grades.'));

            return;
        }

        $this->assessment->delete();

        $this->redirect(route('instructor.assessments.index', $this->section), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('instructor.assessments.index', $section)" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Assessments') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit Assessment') }}</flux:heading>
        <flux:subheading>
            {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }})
        </flux:subheading>
    </div>

    @if (session('error'))
        <flux:callout variant="danger" class="mb-4">
            <flux:callout.heading>{{ session('error') }}</flux:callout.heading>
        </flux:callout>
    @endif

    <form wire:submit="updateAssessment" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="title" :label="__('Title')" type="text" required />

        <flux:select wire:model="type" :label="__('Type')" required>
            @foreach (AssessmentType::cases() as $assessmentType)
                <flux:select.option value="{{ $assessmentType->value }}">{{ ucfirst($assessmentType->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="max_points" :label="__('Max Points')" type="number" min="1" required />
            <flux:input wire:model="sort_order" :label="__('Sort Order')" type="number" min="0" />
        </div>

        <flux:input wire:model="due_date" :label="__('Due Date')" type="date" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Update Assessment') }}</flux:button>
            <flux:button variant="danger" type="button" wire:click="deleteAssessment" wire:confirm="{{ __('Are you sure you want to delete this assessment?') }}">
                {{ __('Delete') }}
            </flux:button>
        </div>
    </form>
</section>
