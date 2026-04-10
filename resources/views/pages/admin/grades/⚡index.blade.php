<?php

use App\Models\Section;
use App\Models\Term;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Grade Management')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $termFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTermFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()
            ->with(['course', 'term', 'instructor'])
            ->when($this->search, function ($query) {
                $query->whereHas('course', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                })->orWhereHas('instructor', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->termFilter, fn ($q) => $q->where('term_id', $this->termFilter))
            ->orderBy('term_id', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function terms()
    {
        return Term::query()->orderBy('start_date', 'desc')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Grade Management') }}</flux:heading>
        <flux:subheading>{{ __('View and manage grades for any section') }}</flux:subheading>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by course or instructor...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="termFilter" placeholder="{{ __('All Terms') }}">
                <flux:select.option value="">{{ __('All Terms') }}</flux:select.option>
                @foreach ($this->terms as $term)
                    <flux:select.option value="{{ $term->id }}">{{ $term->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->sections">
        <flux:table.columns>
            <flux:table.column>{{ __('Course') }}</flux:table.column>
            <flux:table.column>{{ __('Section') }}</flux:table.column>
            <flux:table.column>{{ __('Term') }}</flux:table.column>
            <flux:table.column>{{ __('Instructor') }}</flux:table.column>
            <flux:table.column>{{ __('Enrolled') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->sections as $section)
                <flux:table.row :key="$section->id">
                    <flux:table.cell variant="strong">
                        <div>{{ $section->course->name }}</div>
                        <flux:text variant="subtle" class="text-xs">{{ $section->course->code }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $section->section_number }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $section->term->name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $section->instructor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $section->currentEnrollmentCount() }}/{{ $section->max_enrollment }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end">
                            <flux:button size="sm" :href="route('admin.grades.manage', $section)" wire:navigate>
                                {{ __('Manage') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
