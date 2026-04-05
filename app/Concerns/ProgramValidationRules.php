<?php

namespace App\Concerns;

use App\Models\Program;
use Illuminate\Validation\Rule;

trait ProgramValidationRules
{
    /**
     * Get the validation rules for creating a program.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function programCreateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'alpha', Rule::unique(Program::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:104'],
            'total_credits_required' => ['required', 'integer', 'min:1', 'max:200'],
            'tuition_cost' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get the validation rules for updating a program.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function programUpdateRules(int $programId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'alpha', Rule::unique(Program::class)->ignore($programId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:104'],
            'total_credits_required' => ['required', 'integer', 'min:1', 'max:200'],
            'tuition_cost' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],
        ];
    }
}
