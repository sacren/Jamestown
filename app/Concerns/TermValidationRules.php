<?php

namespace App\Concerns;

use App\Models\Term;
use Illuminate\Validation\Rule;

trait TermValidationRules
{
    /**
     * Get the validation rules for creating a term.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function termCreateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique(Term::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_start' => ['required', 'date', 'before:start_date'],
            'registration_end' => ['required', 'date', 'after:registration_start', 'before_or_equal:start_date'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get the validation rules for updating a term.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function termUpdateRules(int $termId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique(Term::class)->ignore($termId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_start' => ['required', 'date', 'before:start_date'],
            'registration_end' => ['required', 'date', 'after:registration_start', 'before_or_equal:start_date'],
            'is_active' => ['boolean'],
        ];
    }
}
