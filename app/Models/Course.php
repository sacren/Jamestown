<?php

namespace App\Models;

use App\Observers\CourseObserver;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'name', 'code', 'description', 'credit_hours', 'lecture_hours', 'lab_hours', 'tuition_amount', 'is_active'])]
#[ObservedBy([CourseObserver::class])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'credit_hours' => 'integer',
            'lecture_hours' => 'integer',
            'lab_hours' => 'integer',
            'tuition_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'course_id', 'prerequisite_id')
            ->withTimestamps();
    }

    public function prerequisiteFor(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'prerequisite_id', 'course_id')
            ->withTimestamps();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function totalContactHours(): int
    {
        return $this->lecture_hours + $this->lab_hours;
    }
}
