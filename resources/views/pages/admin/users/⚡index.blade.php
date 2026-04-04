<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('User Management')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->role($this->roleFilter);
            })
            ->latest()
            ->paginate(15);
    }

    public function roleColor(string $role): string
    {
        return match ($role) {
            'super-admin' => 'red',
            'admin' => 'amber',
            'registrar' => 'blue',
            'instructor' => 'green',
            'student' => 'zinc',
            default => 'zinc',
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('User Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage all users in the system') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate icon="plus">
            {{ __('Add User') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="roleFilter" placeholder="{{ __('All Roles') }}">
                <flux:select.option value="">{{ __('All Roles') }}</flux:select.option>
                @foreach (Role::cases() as $role)
                    <flux:select.option value="{{ $role->value }}">{{ ucwords(str_replace('-', ' ', $role->value)) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column>{{ __('Joined') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center gap-3">
                            <flux:avatar size="xs" :name="$user->name" :initials="$user->initials()" />
                            {{ $user->name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @foreach ($user->roles as $role)
                            <flux:badge size="sm" :color="$this->roleColor($role->name)" inset="top bottom">
                                {{ ucwords(str_replace('-', ' ', $role->name)) }}
                            </flux:badge>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $user->created_at->format('M d, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('admin.users.edit', $user)" wire:navigate />
                            @if ($user->id !== auth()->id())
                                <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteUser({{ $user->id }})" wire:confirm="{{ __('Are you sure you want to delete this user?') }}" />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
