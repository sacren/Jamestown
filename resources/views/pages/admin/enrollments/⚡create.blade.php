<?php

use App\Concerns\EnrollmentValidationRules;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manual Enrollment')] class extends Component {
    use EnrollmentValidationRules;

    public string $student_id = '';
    public string $section_id = '';

    /** @var array<string, string> */
    public array $eligibilityErrors = [];

    public function enrollStudent(): void
    {
        $this->validate([
            'student_id' => ['required', 'exists:users,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $student = User::with('studentProfile')->findOrFail($this->student_id);
        $section = Section::with(['course.prerequisites', 'schedules', 'term'])->findOrFail($this->section_id);

        $this->eligibilityErrors = $this->validateEnrollmentEligibility($student, $section);

        if (! empty($this->eligibilityErrors)) {
            foreach ($this->eligibilityErrors as $field => $message) {
                $this->addError($field, $message);
            }

            return;
        }

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
        ]);

        $this->redirect(route('admin.enrollments.index'), navigate: true);
    }

    #[Computed]
    public function students()
    {
        return User::role('student')
            ->with('studentProfile')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()
            ->where('is_active', true)
            ->with(['course', 'term', 'instructor'])
            ->withCount(['enrollments' => fn ($q) => $q->where('status', EnrollmentStatus::Enrolled)])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.enrollments.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Enrollments') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Manual Enrollment') }}</flux:heading>
        <flux:subheading>{{ __('Manually enroll a student in a section') }}</flux:subheading>
    </div>

    @if (! empty($eligibilityErrors))
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('Enrollment cannot be completed') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-inside list-disc">
                    @foreach ($eligibilityErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="enrollStudent" class="w-full max-w-lg space-y-6">
        <flux:field>
            <flux:label>{{ __('Student') }}</flux:label>
            <flux:select wire:model="student_id" placeholder="{{ __('Select a student...') }}">
                <flux:select.option value="">{{ __('Select a student...') }}</flux:select.option>
                @foreach ($this->students as $student)
                    <flux:select.option value="{{ $student->id }}">
                        {{ $student->name }} ({{ $student->studentProfile?->student_id_number }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="student_id" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Section') }}</flux:label>
            <flux:select wire:model="section_id" placeholder="{{ __('Select a section...') }}">
                <flux:select.option value="">{{ __('Select a section...') }}</flux:select.option>
                @foreach ($this->sections as $section)
                    <flux:select.option value="{{ $section->id }}">
                        {{ $section->course->code }}-{{ $section->section_number }} — {{ $section->term->name }} ({{ $section->enrollments_count }}/{{ $section->max_enrollment }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="section_id" />
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Enroll Student') }}</flux:button>
        </div>
    </form>
</section>
