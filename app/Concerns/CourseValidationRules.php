<?php

namespace App\Concerns;

use App\Models\Course;
use Illuminate\Validation\Rule;

trait CourseValidationRules
{
    /**
     * Get the validation rules for creating a course.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function courseCreateRules(): array
    {
        return [
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique(Course::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'lecture_hours' => ['required', 'integer', 'min:0', 'max:20'],
            'lab_hours' => ['required', 'integer', 'min:0', 'max:20'],
            'is_active' => ['boolean'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['exists:courses,id'],
        ];
    }

    /**
     * Get the validation rules for updating a course.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function courseUpdateRules(int $courseId): array
    {
        return [
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique(Course::class)->ignore($courseId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'lecture_hours' => ['required', 'integer', 'min:0', 'max:20'],
            'lab_hours' => ['required', 'integer', 'min:0', 'max:20'],
            'is_active' => ['boolean'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['exists:courses,id'],
        ];
    }
}
