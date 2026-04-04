<?php

use App\Concerns\UserManagementValidationRules;
use App\Enums\Role;
use App\Enums\StudentStatus;
use App\Models\InstructorProfile;
use App\Models\StaffProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit User')] class extends Component {
    use UserManagementValidationRules;

    #[Locked]
    public int $userId;

    public string $name = '';
    public string $email = '';
    public string $role = '';

    // Common profile fields
    public string $phone = '';
    public ?string $date_of_birth = null;

    // Student-specific
    public string $emergency_contact_name = '';
    public string $emergency_contact_phone = '';

    // Instructor-specific
    public string $bio = '';

    // Staff-specific
    public string $department = '';
    public string $title = '';

    public function mount(User $user): void
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->date_of_birth = $user->date_of_birth?->format('Y-m-d');
        $this->emergency_contact_name = $user->emergency_contact_name ?? '';
        $this->emergency_contact_phone = $user->emergency_contact_phone ?? '';

        $this->role = $user->roles->first()?->name ?? '';

        if ($user->instructorProfile) {
            $this->bio = $user->instructorProfile->bio ?? '';
        }

        if ($user->staffProfile) {
            $this->department = $user->staffProfile->department ?? '';
            $this->title = $user->staffProfile->title ?? '';
        }
    }

    public function updateUser(): void
    {
        $rules = $this->userUpdateRules($this->userId);

        if (in_array($this->role, [Role::Student->value])) {
            $rules = array_merge($rules, $this->studentProfileRules());
        } elseif (in_array($this->role, [Role::Instructor->value])) {
            $rules = array_merge($rules, $this->instructorProfileRules());
        } elseif (in_array($this->role, [Role::Admin->value, Role::SuperAdmin->value, Role::Registrar->value])) {
            $rules = array_merge($rules, $this->staffProfileRules());
        }

        $validated = $this->validate($rules);

        $user = User::findOrFail($this->userId);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
        ]);

        // Only sync role if not editing self
        if ($user->id !== auth()->id()) {
            $user->syncRoles([$validated['role']]);
            $this->syncProfile($user, $validated);
        }

        $this->dispatch('user-updated');
    }

    public function deleteUser(): void
    {
        $user = User::findOrFail($this->userId);

        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    private function syncProfile(User $user, array $validated): void
    {
        match ($this->role) {
            Role::Student->value => $user->studentProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_id_number' => $user->studentProfile?->student_id_number ?? StudentProfile::generateIdNumber(),
                    'status' => $user->studentProfile?->status ?? StudentStatus::Active,
                ],
            ),
            Role::Instructor->value => $user->instructorProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => $user->instructorProfile?->employee_id ?? InstructorProfile::generateEmployeeId(),
                    'bio' => $validated['bio'] ?? null,
                ],
            ),
            Role::Admin->value, Role::SuperAdmin->value, Role::Registrar->value => $user->staffProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => $user->staffProfile?->employee_id ?? StaffProfile::generateEmployeeId(),
                    'department' => $validated['department'] ?? null,
                    'title' => $validated['title'] ?? null,
                ],
            ),
            default => null,
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Users') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit User') }}</flux:heading>
        <flux:subheading>{{ __('Update user information and role') }}</flux:subheading>
    </div>

    <form wire:submit="updateUser" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />

        @if ($userId !== auth()->id())
            <flux:select wire:model.live="role" :label="__('Role')">
                @foreach (Role::cases() as $roleOption)
                    <flux:select.option value="{{ $roleOption->value }}">{{ ucwords(str_replace('-', ' ', $roleOption->value)) }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <div>
                <flux:text class="text-sm font-medium">{{ __('Role') }}</flux:text>
                <flux:badge class="mt-1">{{ ucwords(str_replace('-', ' ', $role)) }}</flux:badge>
                <flux:text variant="subtle" class="mt-1 text-xs">{{ __('You cannot change your own role.') }}</flux:text>
            </div>
        @endif

        <flux:input wire:model="phone" :label="__('Phone')" type="tel" />

        @if (in_array($role, [\App\Enums\Role::Student->value, \App\Enums\Role::Instructor->value]))
            <flux:input wire:model="date_of_birth" :label="__('Date of Birth')" type="date" />
        @endif

        @if ($role === \App\Enums\Role::Student->value)
            <flux:separator />
            <flux:heading size="lg">{{ __('Emergency Contact') }}</flux:heading>
            <flux:input wire:model="emergency_contact_name" :label="__('Contact Name')" type="text" />
            <flux:input wire:model="emergency_contact_phone" :label="__('Contact Phone')" type="tel" />
        @endif

        @if ($role === \App\Enums\Role::Instructor->value)
            <flux:separator />
            <flux:heading size="lg">{{ __('Instructor Details') }}</flux:heading>
            <flux:textarea wire:model="bio" :label="__('Bio')" rows="3" />
        @endif

        @if (in_array($role, [\App\Enums\Role::Admin->value, \App\Enums\Role::SuperAdmin->value, \App\Enums\Role::Registrar->value]))
            <flux:separator />
            <flux:heading size="lg">{{ __('Staff Details') }}</flux:heading>
            <flux:input wire:model="department" :label="__('Department')" type="text" />
            <flux:input wire:model="title" :label="__('Title')" type="text" />
        @endif

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Update User') }}</flux:button>

            <x-action-message class="me-3" on="user-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

    @if ($userId !== auth()->id())
        <div class="mt-12">
            <flux:separator />
            <div class="mt-6">
                <flux:heading size="lg">{{ __('Delete User') }}</flux:heading>
                <flux:subheading>{{ __('Permanently remove this user and all associated data.') }}</flux:subheading>
                <flux:button variant="danger" wire:click="deleteUser" wire:confirm="{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}" class="mt-4">
                    {{ __('Delete User') }}
                </flux:button>
            </div>
        </div>
    @endif
</section>
