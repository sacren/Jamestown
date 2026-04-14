<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Announcements')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage-announcements'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteAnnouncement(int $id): void
    {
        Announcement::findOrFail($id)->delete();
    }

    #[Computed]
    public function announcements()
    {
        return Announcement::query()
            ->with('author')
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter, function ($query) {
                match ($this->statusFilter) {
                    'published' => $query->whereNotNull('published_at')->where('published_at', '<=', now()),
                    'draft' => $query->whereNull('published_at'),
                    'scheduled' => $query->whereNotNull('published_at')->where('published_at', '>', now()),
                    default => null,
                };
            })
            ->latest()
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Announcements') }}</flux:heading>
            <flux:subheading>{{ __('Manage announcements for students and instructors') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.announcements.create')" wire:navigate icon="plus">
            {{ __('New Announcement') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
        <div class="flex-1 sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by title...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-44">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="scheduled">{{ __('Scheduled') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if ($this->announcements->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle">{{ __('No announcements found') }}</flux:text>
        </div>
    @else
        <flux:table :paginate="$this->announcements">
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Audience') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Published') }}</flux:table.column>
                <flux:table.column>{{ __('Author') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->announcements as $announcement)
                    <flux:table.row :key="$announcement->id">
                        <flux:table.cell variant="strong">{{ $announcement->title }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$announcement->audience->color()" inset="top bottom">
                                {{ $announcement->audience->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($announcement->isPublished())
                                <flux:badge size="sm" color="green" inset="top bottom">{{ __('Published') }}</flux:badge>
                            @elseif ($announcement->isScheduled())
                                <flux:badge size="sm" color="blue" inset="top bottom">{{ __('Scheduled') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ __('Draft') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $announcement->published_at?->format('M j, Y g:ia') ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $announcement->author?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" :href="route('admin.announcements.edit', $announcement)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deleteAnnouncement({{ $announcement->id }})" wire:confirm="{{ __('Are you sure you want to delete this announcement?') }}">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
