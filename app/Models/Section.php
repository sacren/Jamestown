<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'term_id', 'instructor_id', 'section_number', 'max_enrollment', 'is_active'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_enrollment' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SectionSchedule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollmentCount(): int
    {
        return $this->enrollments()->where('status', EnrollmentStatus::Enrolled)->count();
    }

    public function displayCode(): string
    {
        return $this->course->code . '-' . $this->section_number;
    }

    public function scheduleSummary(): string
    {
        $dayAbbrev = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
        ];

        return $this->schedules
            ->groupBy(fn ($s) => $s->start_time . '-' . $s->end_time)
            ->map(function ($group, $time) use ($dayAbbrev) {
                $days = $group->map(fn ($s) => $dayAbbrev[$s->day_of_week->value] ?? $s->day_of_week->value)->implode('/');

                return $days . ' ' . $time;
            })
            ->implode(', ');
    }
}
