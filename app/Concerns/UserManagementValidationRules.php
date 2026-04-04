<?php

namespace App\Concerns;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait UserManagementValidationRules
{
    /**
     * Get the validation rules for creating a user.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function userCreateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'role' => ['required', 'string', Rule::in(array_column(Role::cases(), 'value'))],
        ];
    }

    /**
     * Get the validation rules for updating a user.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function userUpdateRules(int $userId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($userId)],
            'role' => ['required', 'string', Rule::in(array_column(Role::cases(), 'value'))],
        ];
    }

    /**
     * Get the validation rules for common profile fields.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function commonProfileRules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ];
    }

    /**
     * Get the validation rules for student-specific fields.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function studentProfileRules(): array
    {
        return [
            ...$this->commonProfileRules(),
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Get the validation rules for instructor-specific fields.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function instructorProfileRules(): array
    {
        return [
            ...$this->commonProfileRules(),
            'bio' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the validation rules for staff-specific fields.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function staffProfileRules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
