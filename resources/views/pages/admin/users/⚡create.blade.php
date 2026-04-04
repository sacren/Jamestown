<?php

use App\Concerns\UserManagementValidationRules;
use App\Enums\Role;
use App\Enums\StudentStatus;
use App\Models\InstructorProfile;
use App\Models\StaffProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create User')] class extends Component {
    use UserManagementValidationRules;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
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

    public function createUser(): void
    {
        $rules = $this->userCreateRules();

        if (in_array($this->role, [Role::Student->value])) {
            $rules = array_merge($rules, $this->studentProfileRules());
        } elseif (in_array($this->role, [Role::Instructor->value])) {
            $rules = array_merge($rules, $this->instructorProfileRules());
        } elseif (in_array($this->role, [Role::Admin->value, Role::SuperAdmin->value, Role::Registrar->value])) {
            $rules = array_merge($rules, $this->staffProfileRules());
        }

        $validated = $this->validate($rules);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        $this->createProfile($user, $validated);

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    private function createProfile(User $user, array $validated): void
    {
        match ($this->role) {
            Role::Student->value => $user->studentProfile()->create([
                'student_id_number' => StudentProfile::generateIdNumber(),
                'enrollment_date' => now(),
                'status' => StudentStatus::Active,
            ]),
            Role::Instructor->value => $user->instructorProfile()->create([
                'employee_id' => InstructorProfile::generateEmployeeId(),
                'hire_date' => now(),
                'bio' => $validated['bio'] ?? null,
            ]),
            Role::Admin->value, Role::SuperAdmin->value, Role::Registrar->value => $user->staffProfile()->create([
                'employee_id' => StaffProfile::generateEmployeeId(),
                'department' => $validated['department'] ?? null,
                'title' => $validated['title'] ?? null,
            ]),
            default => null,
        };

        if ($this->role === Role::Student->value) {
            $user->update([
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            ]);
        }
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Users') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
        <flux:subheading>{{ __('Add a new user to the system') }}</flux:subheading>
    </div>

    <form wire:submit="createUser" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="password" :label="__('Password')" type="password" required />
        <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" required />

        <flux:select wire:model.live="role" :label="__('Role')" placeholder="{{ __('Select a role...') }}">
            @foreach (Role::cases() as $roleOption)
                <flux:select.option value="{{ $roleOption->value }}">{{ ucwords(str_replace('-', ' ', $roleOption->value)) }}</flux:select.option>
            @endforeach
        </flux:select>

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
            <flux:button variant="primary" type="submit">{{ __('Create User') }}</flux:button>
        </div>
    </form>
</section>
