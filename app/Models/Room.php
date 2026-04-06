<?php

namespace App\Models;

use App\Enums\RoomType;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'building', 'capacity', 'type', 'description', 'is_active'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'type' => RoomType::class,
            'is_active' => 'boolean',
        ];
    }

    public function sectionSchedules(): HasMany
    {
        return $this->hasMany(SectionSchedule::class);
    }
}
