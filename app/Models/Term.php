<?php

namespace App\Models;

use App\Observers\TermObserver;
use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'start_date', 'end_date', 'registration_start', 'registration_end', 'is_active'])]
#[ObservedBy([TermObserver::class])]
class Term extends Model
{
    /** @use HasFactory<TermFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_start' => 'date',
            'registration_end' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function activeSections(): HasMany
    {
        return $this->hasMany(Section::class)->where('is_active', true);
    }
}
