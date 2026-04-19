<?php

namespace App\Models;

use App\Observers\ProgramObserver;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'code', 'slug', 'description', 'duration_weeks', 'total_credits_required', 'tuition_cost', 'is_active'])]
#[ObservedBy([ProgramObserver::class])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Program $program): void {
            if (empty($program->slug) && ! empty($program->name)) {
                $program->slug = static::generateUniqueSlug($program->name, $program->getKey());
            }
        });
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    protected function casts(): array
    {
        return [
            'duration_weeks' => 'integer',
            'total_credits_required' => 'integer',
            'tuition_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function activeCourses(): HasMany
    {
        return $this->hasMany(Course::class)->where('is_active', true);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
