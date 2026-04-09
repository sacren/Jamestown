<?php

use App\Concerns\AttendanceValidationRules;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Section;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Record Attendance')] class extends Component {
    use AttendanceValidationRules;

    public Section $section;

    public string $date = '';

    /** @var array<int, array{status: string, notes: string}> */
    public array $attendances = [];

    /** @var array<int, string> */
    public array $dateErrors = [];

    public function mount(Section $section): void
    {
        $this->section = $section->load('course', 'term', 'schedules');
        $this->date = now()->toDateString();
        $this->loadStudents();
    }

    public function updatedDate(): void
    {
        $this->loadStudents();
    }

    public function loadStudents(): void
    {
        $students = $this->getEnrolledStudentsForSection($this->section);
        $schedule = $this->getScheduleForDate($this->section, $this->date);

        $existing = collect();
        if ($schedule) {
            $existing = Attendance::query()
                ->where('section_schedule_id', $schedule->id)
                ->where('date', $this->date)
                ->get()
                ->keyBy('enrollment_id');
        }

        $this->attendances = [];
        foreach ($students as $enrollment) {
            $record = $existing->get($enrollment->id);
            $this->attendances[$enrollment->id] = [
                'status' => $record ? $record->status->value : AttendanceStatus::Present->value,
                'notes' => $record?->notes ?? '',
            ];
        }
    }

    public function saveAttendance(): void
    {
        $this->dateErrors = $this->validateAttendanceDate($this->section, $this->date);

        if (! empty($this->dateErrors)) {
            return;
        }

        $schedule = $this->getScheduleForDate($this->section, $this->date);

        foreach ($this->attendances as $enrollmentId => $data) {
            Attendance::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'section_schedule_id' => $schedule->id,
                    'date' => $this->date,
                ],
                [
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?: null,
                    'recorded_by' => auth()->id(),
                ],
            );
        }

        session()->flash('status', __('Attendance saved.'));
    }

    public function students()
    {
        return $this->getEnrolledStudentsForSection($this->section);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Record Attendance') }}</flux:heading>
        <flux:subheading>
            {{ $section->course->code }} - {{ $section->course->name }} ({{ $section->section_number }}) — {{ $section->term->name }}
        </flux:subheading>
    </div>

    @if (session('status'))
        <flux:callout variant="success" class="mb-4">
            <flux:callout.heading>{{ session('status') }}</flux:callout.heading>
        </flux:callout>
    @endif

    @if (! empty($dateErrors))
        <flux:callout variant="danger" class="mb-4">
            <flux:callout.heading>{{ __('Cannot save attendance') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-disc pl-5">
                    @foreach ($dateErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="mb-4 w-full sm:w-60">
        <flux:input type="date" wire:model.live="date" label="{{ __('Date') }}" />
    </div>

    @if ($this->students()->isEmpty())
        <flux:callout>
            <flux:callout.heading>{{ __('No enrolled students') }}</flux:callout.heading>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
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
                            <flux:select wire:model="attendances.{{ $enrollment->id }}.status">
                                @foreach (AttendanceStatus::cases() as $status)
                                    <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:input wire:model="attendances.{{ $enrollment->id }}.notes" placeholder="{{ __('Optional notes') }}" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4 flex justify-end">
            <flux:button variant="primary" wire:click="saveAttendance">
                {{ __('Save Attendance') }}
            </flux:button>
        </div>
    @endif
</section>
