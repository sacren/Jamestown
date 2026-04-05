<?php

use App\Concerns\CourseValidationRules;
use App\Models\Course;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Course')] class extends Component {
    use CourseValidationRules;

    #[Locked]
    public int $courseId;

    public string $program_id = '';
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $credit_hours = null;
    public ?int $lecture_hours = null;
    public ?int $lab_hours = null;
    public bool $is_active = true;
    public array $prerequisite_ids = [];

    public function mount(Course $course): void
    {
        $this->courseId = $course->id;
        $this->program_id = (string) $course->program_id;
        $this->name = $course->name;
        $this->code = $course->code;
        $this->description = $course->description ?? '';
        $this->credit_hours = $course->credit_hours;
        $this->lecture_hours = $course->lecture_hours;
        $this->lab_hours = $course->lab_hours;
        $this->is_active = $course->is_active;
        $this->prerequisite_ids = $course->prerequisites->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function updateCourse(): void
    {
        $validated = $this->validate($this->courseUpdateRules($this->courseId));

        $course = Course::findOrFail($this->courseId);

        $course->update([
            'program_id' => $validated['program_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'credit_hours' => $validated['credit_hours'],
            'lecture_hours' => $validated['lecture_hours'],
            'lab_hours' => $validated['lab_hours'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $course->prerequisites()->sync($validated['prerequisite_ids'] ?? []);

        $this->dispatch('course-updated');
    }

    public function deleteCourse(): void
    {
        Course::findOrFail($this->courseId)->delete();

        $this->redirect(route('admin.courses.index'), navigate: true);
    }

    #[Computed]
    public function programs()
    {
        return Program::query()->orderBy('name')->get();
    }

    #[Computed]
    public function availableCourses()
    {
        return Course::query()->where('id', '!=', $this->courseId)->orderBy('code')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.courses.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Courses') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit Course') }}</flux:heading>
        <flux:subheading>{{ __('Update course information') }}</flux:subheading>
    </div>

    <form wire:submit="updateCourse" class="w-full max-w-lg space-y-6">
        <flux:select wire:model="program_id" :label="__('Program')" required>
            @foreach ($this->programs as $program)
                <flux:select.option value="{{ $program->id }}">{{ $program->name }} ({{ $program->code }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="name" :label="__('Course Name')" type="text" required />
        <flux:input wire:model="code" :label="__('Course Code')" type="text" required />
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
            <flux:button variant="primary" type="submit">{{ __('Update Course') }}</flux:button>

            <x-action-message class="me-3" on="course-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

    <div class="mt-12">
        <flux:separator />
        <div class="mt-6">
            <flux:heading size="lg">{{ __('Delete Course') }}</flux:heading>
            <flux:subheading>{{ __('Permanently remove this course.') }}</flux:subheading>
            <flux:button variant="danger" wire:click="deleteCourse" wire:confirm="{{ __('Are you sure you want to delete this course? This action cannot be undone.') }}" class="mt-4">
                {{ __('Delete Course') }}
            </flux:button>
        </div>
    </div>
</section>
