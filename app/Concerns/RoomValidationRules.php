<?php

namespace App\Concerns;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait RoomValidationRules
{
    /**
     * Get the validation rules for creating a room.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function roomCreateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique(Room::class)],
            'building' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'type' => ['required', new Enum(RoomType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get the validation rules for updating a room.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function roomUpdateRules(int $roomId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique(Room::class)->ignore($roomId)],
            'building' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'type' => ['required', new Enum(RoomType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];
    }
}
