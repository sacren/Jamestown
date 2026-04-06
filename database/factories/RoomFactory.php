<?php

namespace Database\Factories;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Room> */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true) . ' Room',
            'code' => strtoupper(fake()->unique()->bothify('??-###')),
            'building' => fake()->randomElement(['Main Building', 'Trade Building', 'Annex']),
            'capacity' => fake()->numberBetween(15, 30),
            'type' => fake()->randomElement(RoomType::cases()),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function classroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RoomType::Classroom,
        ]);
    }

    public function lab(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RoomType::Lab,
        ]);
    }

    public function shop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RoomType::Shop,
        ]);
    }
}
