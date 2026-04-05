<?php

use App\Models\Course;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Course Management')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $programFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_active' => ! $course->is_active]);
    }

    public function deleteCourse(int $courseId): void
    {
        Course::findOrFail($courseId)->delete();
    }

    #[Computed]
    public function courses()
    {
        return Course::query()
            ->with('program')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->programFilter, function ($query) {
                $query->where('program_id', $this->programFilter);
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function programs()
    {
        return Program::query()->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Course Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage courses across all programs') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.courses.create')" wire:navigate icon="plus">
            {{ __('Add Course') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or code...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="programFilter" placeholder="{{ __('All Programs') }}">
                <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
                @foreach ($this->programs as $program)
                    <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->courses">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Program') }}</flux:table.column>
            <flux:table.column>{{ __('Credits') }}</flux:table.column>
            <flux:table.column>{{ __('Contact Hours') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->courses as $course)
                <flux:table.row :key="$course->id">
                    <flux:table.cell variant="strong">{{ $course->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $course->code }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $course->program->name }}</flux:table.cell>
                    <flux:table.cell>{{ $course->credit_hours }}</flux:table.cell>
                    <flux:table.cell>{{ $course->totalContactHours() }}h ({{ $course->lecture_hours }}L + {{ $course->lab_hours }}B)</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$course->is_active ? 'green' : 'zinc'" inset="top bottom">
                            {{ $course->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('admin.courses.edit', $course)" wire:navigate />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteCourse({{ $course->id }})" wire:confirm="{{ __('Are you sure you want to delete this course?') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
