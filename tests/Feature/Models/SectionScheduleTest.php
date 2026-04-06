<?php

use App\Enums\DayOfWeek;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;

test('section schedule has fillable attributes', function () {
    $schedule = SectionSchedule::factory()->create([
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    expect($schedule->start_time)->toContain('09:00');
    expect($schedule->end_time)->toContain('10:30');
});

test('section schedule belongs to section', function () {
    $section = Section::factory()->create();
    $schedule = SectionSchedule::factory()->forSection($section)->create();

    expect($schedule->section->id)->toBe($section->id);
});

test('section schedule belongs to room', function () {
    $room = Room::factory()->create();
    $schedule = SectionSchedule::factory()->forRoom($room)->create();

    expect($schedule->room->id)->toBe($room->id);
});

test('section schedule casts day_of_week to DayOfWeek enum', function () {
    $schedule = SectionSchedule::factory()->create(['day_of_week' => DayOfWeek::Monday]);

    expect($schedule->day_of_week)->toBe(DayOfWeek::Monday);
    expect($schedule->day_of_week)->toBeInstanceOf(DayOfWeek::class);
});

test('deleting section cascades to schedules', function () {
    $section = Section::factory()->create();
    SectionSchedule::factory()->forSection($section)->count(3)->create();

    expect(SectionSchedule::where('section_id', $section->id)->count())->toBe(3);

    $section->delete();

    expect(SectionSchedule::where('section_id', $section->id)->count())->toBe(0);
});
