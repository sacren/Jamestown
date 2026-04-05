<?php

use App\Concerns\CourseValidationRules;
use App\Models\Course;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Course')] class extends Component {
    use CourseValidationRules;

    public string $program_id = '';
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $credit_hours = null;
    public ?int $lecture_hours = null;
    public ?int $lab_hours = null;
    public bool $is_active = true;
    public array $prerequisite_ids = [];

    public function createCourse(): void
    {
        $validated = $this->validate($this->courseCreateRules());

        $course = Course::create([
            'program_id' => $validated['program_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'credit_hours' => $validated['credit_hours'],
            'lecture_hours' => $validated['lecture_hours'],
            'lab_hours' => $validated['lab_hours'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['prerequisite_ids'])) {
            $course->prerequisites()->attach($validated['prerequisite_ids']);
        }

        $this->redirect(route('admin.courses.index'), navigate: true);
    }

    #[Computed]
    public function programs()
    {
        return Program::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function availableCourses()
    {
        return Course::query()->orderBy('code')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.courses.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Courses') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create Course') }}</flux:heading>
        <flux:subheading>{{ __('Add a new course to a program') }}</flux:subheading>
    </div>

    <form wire:submit="createCourse" class="w-full max-w-lg space-y-6">
        <flux:select wire:model="program_id" :label="__('Program')" placeholder="{{ __('Select a program...') }}" required>
            @foreach ($this->programs as $program)
                <flux:select.option value="{{ $program->id }}">{{ $program->name }} ({{ $program->code }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="name" :label="__('Course Name')" type="text" required />
        <flux:input wire:model="code" :label="__('Course Code')" type="text" required placeholder="e.g. WLD-101" />
        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

        <div class="grid grid-cols-3 gap-4">
            <flux:input wire:model="credit_hours" :label="__('Credit Hours')" type="number" min="1" max="12" required />
            <flux:input wire:model="lecture_hours" :label="__('Lecture Hours')" type="number" min="0" max="20" required />
            <flux:input wire:model="lab_hours" :label="__('Lab Hours')" type="number" min="0" max="20" required />
        </div>

        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        @if ($this->availableCourses->isNotEmpty())
            <flux:separator />
            <flux:heading size="lg">{{ __('Prerequisites') }}</flux:heading>
            <flux:text variant="subtle" class="mb-2">{{ __('Select courses that must be completed before this course.') }}</flux:text>
            <div class="space-y-2">
                @foreach ($this->availableCourses as $course)
                    <flux:checkbox wire:model="prerequisite_ids" value="{{ $course->id }}" :label="$course->code.' - '.$course->name" />
                @endforeach
            </div>
        @endif

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create Course') }}</flux:button>
        </div>
    </form>
</section>
