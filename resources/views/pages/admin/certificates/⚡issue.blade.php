<?php

use App\Actions\Documents\CheckProgramCompletion;
use App\Actions\Documents\IssueCertificate;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\User;
use App\Notifications\CertificateIssued;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Issue Certificate')] class extends Component {
    public string $student_id = '';
    public string $program_id = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('certificates.manage'), 403);
    }

    public function updatedStudentId(): void
    {
        $this->program_id = '';
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
    public function availablePrograms()
    {
        if (! $this->student_id) {
            return collect();
        }

        $programIds = Enrollment::query()
            ->where('user_id', $this->student_id)
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->join('courses', 'sections.course_id', '=', 'courses.id')
            ->distinct()
            ->pluck('courses.program_id');

        return Program::whereIn('id', $programIds)->orderBy('name')->get();
    }

    #[Computed]
    public function completionStatus(): ?array
    {
        if (! $this->student_id || ! $this->program_id) {
            return null;
        }

        $student = User::find($this->student_id);
        $program = Program::find($this->program_id);

        if (! $student || ! $program) {
            return null;
        }

        return app(CheckProgramCompletion::class)->handle($student, $program);
    }

    public function issueCertificate(): void
    {
        $this->validate([
            'student_id' => ['required', 'exists:users,id'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        $student = User::findOrFail($this->student_id);
        $program = Program::findOrFail($this->program_id);

        try {
            $certificate = app(IssueCertificate::class)->handle($student, $program, auth()->user());
            $student->notify(new CertificateIssued($certificate));
            $this->redirect(route('admin.certificates.show', $certificate), navigate: true);
        } catch (\InvalidArgumentException $e) {
            $this->addError('program_id', $e->getMessage());
        }
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.certificates.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Certificates') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Issue Certificate') }}</flux:heading>
        <flux:subheading>{{ __('Issue a program completion certificate to a student') }}</flux:subheading>
    </div>

    <form wire:submit="issueCertificate" class="w-full max-w-lg space-y-6">
        <flux:field>
            <flux:label>{{ __('Student') }}</flux:label>
            <flux:select wire:model.live="student_id" placeholder="{{ __('Select a student...') }}">
                <flux:select.option value="">{{ __('Select a student...') }}</flux:select.option>
                @foreach ($this->students as $student)
                    <flux:select.option value="{{ $student->id }}">
                        {{ $student->name }} ({{ $student->studentProfile?->student_id_number }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="student_id" />
        </flux:field>

        @if ($student_id)
        <flux:field>
            <flux:label>{{ __('Program') }}</flux:label>
            <flux:select wire:model.live="program_id" placeholder="{{ __('Select a program...') }}">
                <flux:select.option value="">{{ __('Select a program...') }}</flux:select.option>
                @foreach ($this->availablePrograms as $program)
                    <flux:select.option value="{{ $program->id }}">
                        {{ $program->name }} ({{ $program->code }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="program_id" />
        </flux:field>
        @endif

        @if ($this->completionStatus)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" class="mb-3">{{ __('Completion Progress') }}</flux:heading>
                <flux:text class="mb-3">
                    {{ $this->completionStatus['completed_courses']->count() }} {{ __('of') }} {{ $this->completionStatus['total_courses'] }} {{ __('courses completed') }}
                </flux:text>

                <div class="space-y-2">
                    @foreach ($this->completionStatus['completed_courses'] as $course)
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="green" inset="top bottom">{{ __('Completed') }}</flux:badge>
                            <flux:text>{{ $course->code }} — {{ $course->name }}</flux:text>
                        </div>
                    @endforeach
                    @foreach ($this->completionStatus['in_progress_courses'] as $course)
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="blue" inset="top bottom">{{ __('In Progress') }}</flux:badge>
                            <flux:text>{{ $course->code }} — {{ $course->name }}</flux:text>
                        </div>
                    @endforeach
                    @foreach ($this->completionStatus['remaining_courses'] as $course)
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ __('Not Started') }}</flux:badge>
                            <flux:text>{{ $course->code }} — {{ $course->name }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4">
                @if ($this->completionStatus['eligible'])
                    <flux:button variant="primary" type="submit">{{ __('Issue Certificate') }}</flux:button>
                @else
                    <flux:button variant="primary" type="submit" disabled>{{ __('Issue Certificate') }}</flux:button>
                    <flux:text variant="subtle">{{ __('Student must complete all courses before a certificate can be issued.') }}</flux:text>
                @endif
            </div>
        @endif
    </form>
</section>
