<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Section Management')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $termFilter = '';

    #[Url]
    public string $programFilter = '';

    #[Url]
    public string $instructorFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTermFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatedInstructorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $sectionId): void
    {
        $section = Section::findOrFail($sectionId);
        $section->update(['is_active' => ! $section->is_active]);
    }

    public function deleteSection(int $sectionId): void
    {
        Section::findOrFail($sectionId)->delete();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()
            ->with(['course.program', 'term', 'instructor', 'schedules.room'])
            ->when($this->search, function ($query) {
                $query->whereHas('course', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->termFilter, fn ($q) => $q->where('term_id', $this->termFilter))
            ->when($this->programFilter, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('program_id', $this->programFilter)))
            ->when($this->instructorFilter, fn ($q) => $q->where('instructor_id', $this->instructorFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function terms()
    {
        return Term::query()->orderBy('start_date', 'desc')->get();
    }

    #[Computed]
    public function programs()
    {
        return Program::query()->orderBy('name')->get();
    }

    #[Computed]
    public function instructors()
    {
        return User::role('instructor')->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Section Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage course sections and schedules') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.sections.create')" wire:navigate icon="plus">
            {{ __('Add Section') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by course name or code...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('All Terms') }}">
                <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="programFilter" placeholder="{{ __('All Programs') }}">
                <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
                @foreach ($this->programs as $program)
                    <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="instructorFilter" placeholder="{{ __('All Instructors') }}">
                <flux:select.option value="">{{ __('All Instructors') }}</flux:select.option>
                @foreach ($this->instructors as $instructor)
                    <flux:select.option value="{{ $instructor->id }}">{{ $instructor->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-36">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->sections">
        <flux:table.columns>
            <flux:table.column>{{ __('Course') }}</flux:table.column>
            <flux:table.column>{{ __('Section') }}</flux:table.column>
            <flux:table.column>{{ __('Term') }}</flux:table.column>
            <flux:table.column>{{ __('Instructor') }}</flux:table.column>
            <flux:table.column>{{ __('Max') }}</flux:table.column>
            <flux:table.column>{{ __('Schedule') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->sections as $section)
                <flux:table.row :key="$section->id">
                    <flux:table.cell variant="strong">
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $section->course->code }}</flux:badge>
                        <span class="ml-1">{{ $section->course->name }}</span>
                    </flux:table.cell>
                    <flux:table.cell>{{ $section->section_number }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $section->term->name }}</flux:table.cell>
                    <flux:table.cell>{{ $section->instructor?->name ?? __('Unassigned') }}</flux:table.cell>
                    <flux:table.cell>{{ $section->max_enrollment }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $section->scheduleSummary() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$section->is_active ? 'green' : 'zinc'" inset="top bottom">
                            {{ $section->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('admin.sections.edit', $section)" wire:navigate />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteSection({{ $section->id }})" wire:confirm="{{ __('Are you sure you want to delete this section?') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
