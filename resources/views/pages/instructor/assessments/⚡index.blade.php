<?php

use App\Models\Assessment;
use App\Models\Section;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Assessments')] class extends Component {
    public Section $section;

    public function mount(Section $section): void
    {
        abort_unless($section->instructor_id === auth()->id(), 403);

        $this->section = $section->load('course', 'term');
    }

    public function deleteAssessment(int $assessmentId): void
    {
        $assessment = Assessment::query()
            ->where('section_id', $this->section->id)
            ->findOrFail($assessmentId);

        if ($assessment->grades()->exists()) {
            session()->flash('error', __('Cannot delete an assessment that has grades.'));

            return;
        }

        $assessment->delete();

        session()->flash('status', __('Assessment deleted.'));
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
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Assessments') }}</flux:heading>
            <flux:subheading>
                {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }}) — {{ $section->term->name }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('instructor.assessments.create', $section)" wire:navigate icon="plus">
            {{ __('Create Assessment') }}
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" class="mb-4">
            <flux:callout.heading>{{ session('status') }}</flux:callout.heading>
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" class="mb-4">
            <flux:callout.heading>{{ session('error') }}</flux:callout.heading>
        </flux:callout>
    @endif

    @if ($this->assessments->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No assessments') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Create your first assessment to get started.') }}</flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Max Points') }}</flux:table.column>
                <flux:table.column>{{ __('Due Date') }}</flux:table.column>
                <flux:table.column>{{ __('Order') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->assessments as $assessment)
                    <flux:table.row :key="$assessment->id">
                        <flux:table.cell variant="strong">{{ $assessment->title }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="blue" inset="top bottom">{{ ucfirst($assessment->type->value) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $assessment->max_points }}</flux:table.cell>
                        <flux:table.cell>{{ $assessment->due_date?->format('M j, Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $assessment->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('instructor.assessments.edit', ['section' => $section, 'assessment' => $assessment])" wire:navigate />
                                <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteAssessment({{ $assessment->id }})" wire:confirm="{{ __('Are you sure you want to delete this assessment?') }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
