<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SectionSchedule> */
class SectionScheduleFactory extends Factory
{
    protected $model = SectionSchedule::class;

    public function definition(): array
    {
        return [
            'section_id' => SectionFactory::new(),
            'room_id' => RoomFactory::new(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases()),
            'start_time' => '09:00',
            'end_time' => '10:30',
        ];
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn (array $attributes) => [
            'section_id' => $section->id,
        ]);
    }

    public function forRoom(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => $room->id,
        ]);
    }
}
