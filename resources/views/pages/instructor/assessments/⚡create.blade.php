<?php

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Section;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Assessment')] class extends Component {
    public Section $section;

    public string $title = '';
    public string $type = 'assignment';
    public string $description = '';
    public ?int $max_points = 100;
    public string $due_date = '';
    public int $sort_order = 0;

    public function mount(Section $section): void
    {
        abort_unless($section->instructor_id === auth()->id(), 403);

        $this->section = $section->load('course', 'term');
    }

    public function createAssessment(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column(AssessmentType::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'max_points' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        Assessment::create([
            'section_id' => $this->section->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?: null,
            'max_points' => $validated['max_points'],
            'due_date' => $validated['due_date'] ?: null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->redirect(route('instructor.assessments.index', $this->section), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('instructor.assessments.index', $section)" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Assessments') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create Assessment') }}</flux:heading>
        <flux:subheading>
            {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }})
        </flux:subheading>
    </div>

    <form wire:submit="createAssessment" class="w-full max-w-lg space-y-6">
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
            <flux:button variant="primary" type="submit">{{ __('Create Assessment') }}</flux:button>
        </div>
    </form>
</section>
