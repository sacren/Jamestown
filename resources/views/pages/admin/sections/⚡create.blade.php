<?php

use App\Concerns\ChecksScheduleConflicts;
use App\Concerns\SectionValidationRules;
use App\Enums\DayOfWeek;
use App\Models\Course;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Section')] class extends Component {
    use ChecksScheduleConflicts;
    use SectionValidationRules;

    public string $term_id = '';
    public string $course_id = '';
    public string $instructor_id = '';
    public string $section_number = '';
    public ?int $max_enrollment = null;
    public bool $is_active = true;

    /** @var array<int, array{day_of_week: string, start_time: string, end_time: string, room_id: string}> */
    public array $schedules = [
        ['day_of_week' => '', 'start_time' => '', 'end_time' => '', 'room_id' => ''],
    ];

    public function addSchedule(): void
    {
        $this->schedules[] = ['day_of_week' => '', 'start_time' => '', 'end_time' => '', 'room_id' => ''];
    }

    public function removeSchedule(int $index): void
    {
        if (count($this->schedules) > 1) {
            unset($this->schedules[$index]);
            $this->schedules = array_values($this->schedules);
        }
    }

    public function createSection(): void
    {
        $validated = $this->validate($this->sectionCreateRules());

        // Check composite unique constraint
        $exists = Section::where('course_id', $validated['course_id'])
            ->where('term_id', $validated['term_id'])
            ->where('section_number', $validated['section_number'])
            ->exists();

        if ($exists) {
            $this->addError('section_number', __('This section number already exists for this course and term.'));

            return;
        }

        // Check for schedule conflicts
        foreach ($validated['schedules'] as $index => $schedule) {
            if (! empty($schedule['room_id'])) {
                if ($this->checkRoomConflict(
                    (int) $validated['term_id'],
                    (int) $schedule['room_id'],
                    $schedule['day_of_week'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                )) {
                    $this->addError("schedules.{$index}.room_id", __('This room has a schedule conflict at this time.'));

                    return;
                }
            }

            if (! empty($validated['instructor_id'])) {
                if ($this->checkInstructorConflict(
                    (int) $validated['term_id'],
                    (int) $validated['instructor_id'],
                    $schedule['day_of_week'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                )) {
                    $this->addError('instructor_id', __('This instructor has a schedule conflict at this time.'));

                    return;
                }
            }
        }

        $section = Section::create([
            'course_id' => $validated['course_id'],
            'term_id' => $validated['term_id'],
            'instructor_id' => $validated['instructor_id'] ?: null,
            'section_number' => $validated['section_number'],
            'max_enrollment' => $validated['max_enrollment'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['schedules'] as $schedule) {
            SectionSchedule::create([
                'section_id' => $section->id,
                'room_id' => $schedule['room_id'] ?: null,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
            ]);
        }

        $this->redirect(route('admin.sections.index'), navigate: true);
    }

    #[Computed]
    public function terms()
    {
        return Term::query()->where('is_active', true)->orderBy('start_date', 'desc')->get();
    }

    #[Computed]
    public function courses()
    {
        return Course::query()->where('is_active', true)->with('program')->orderBy('code')->get();
    }

    #[Computed]
    public function instructors()
    {
        return User::role('instructor')->orderBy('name')->get();
    }

    #[Computed]
    public function rooms()
    {
        return Room::query()->where('is_active', true)->orderBy('code')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.sections.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Sections') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create Section') }}</flux:heading>
        <flux:subheading>{{ __('Add a new course section with schedule') }}</flux:subheading>
    </div>

    <form wire:submit="createSection" class="w-full max-w-2xl space-y-6">
        <flux:select wire:model="term_id" :label="__('Term')" placeholder="{{ __('Select a term...') }}" required>
            @foreach ($this->terms as $term)
                <flux:select.option value="{{ $term->id }}">{{ $term->name }} ({{ $term->code }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="course_id" :label="__('Course')" placeholder="{{ __('Select a course...') }}" required>
            @foreach ($this->courses as $course)
                <flux:select.option value="{{ $course->id }}">{{ $course->code }} - {{ $course->name }} ({{ $course->program->name }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="instructor_id" :label="__('Instructor')">
            <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
            @foreach ($this->instructors as $instructor)
                <flux:select.option value="{{ $instructor->id }}">{{ $instructor->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="section_number" :label="__('Section Number')" type="text" required placeholder="e.g. 01" />
            <flux:input wire:model="max_enrollment" :label="__('Max Enrollment')" type="number" min="1" max="100" required />
        </div>

        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        <flux:separator />

        <div>
            <flux:heading size="lg">{{ __('Schedule') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Add meeting times for this section. At least one is required.') }}</flux:text>
        </div>

        @foreach ($schedules as $index => $schedule)
            <div wire:key="schedule-{{ $index }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Meeting') }} {{ $index + 1 }}</flux:heading>
                    @if (count($schedules) > 1)
                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeSchedule({{ $index }})" type="button" />
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="schedules.{{ $index }}.day_of_week" :label="__('Day')" required>
                        <flux:select.option value="">{{ __('Select day...') }}</flux:select.option>
                        @foreach (DayOfWeek::cases() as $day)
                            <flux:select.option value="{{ $day->value }}">{{ ucfirst($day->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="schedules.{{ $index }}.room_id" :label="__('Room')">
                        <flux:select.option value="">{{ __('No room') }}</flux:select.option>
                        @foreach ($this->rooms as $room)
                            <flux:select.option value="{{ $room->id }}">{{ $room->code }} - {{ $room->name }} ({{ $room->capacity }})</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:input wire:model="schedules.{{ $index }}.start_time" :label="__('Start Time')" type="time" required />
                    <flux:input wire:model="schedules.{{ $index }}.end_time" :label="__('End Time')" type="time" required />
                </div>
            </div>
        @endforeach

        <flux:button variant="ghost" icon="plus" wire:click="addSchedule" type="button">
            {{ __('Add Meeting Time') }}
        </flux:button>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create Section') }}</flux:button>
        </div>
    </form>
</section>
