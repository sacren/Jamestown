<?php

namespace App\Concerns;

use App\Enums\DayOfWeek;
use Illuminate\Validation\Rules\Enum;

trait SectionValidationRules
{
    /**
     * Get the validation rules for creating a section.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function sectionCreateRules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'section_number' => ['required', 'string', 'max:10'],
            'max_enrollment' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['boolean'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', new Enum(DayOfWeek::class)],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room_id' => ['nullable', 'exists:rooms,id'],
        ];
    }

    /**
     * Get the validation rules for updating a section.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function sectionUpdateRules(int $sectionId): array
    {
        return $this->sectionCreateRules();
    }
}
