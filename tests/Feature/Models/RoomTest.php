<?php

use App\Enums\RoomType;
use App\Models\Room;
use App\Models\SectionSchedule;

test('room has fillable attributes', function () {
    $room = Room::factory()->create([
        'name' => 'Welding Shop A',
        'code' => 'WS-A',
        'building' => 'Trade Building',
    ]);

    expect($room->name)->toBe('Welding Shop A');
    expect($room->code)->toBe('WS-A');
    expect($room->building)->toBe('Trade Building');
});

test('room casts type to RoomType enum', function () {
    $room = Room::factory()->classroom()->create();

    expect($room->type)->toBe(RoomType::Classroom);
    expect($room->type)->toBeInstanceOf(RoomType::class);
});

test('room casts is_active to boolean', function () {
    $room = Room::factory()->create(['is_active' => true]);

    expect($room->is_active)->toBeBool();
    expect($room->is_active)->toBeTrue();
});

test('room has many section schedules', function () {
    $room = Room::factory()->create();
    SectionSchedule::factory()->forRoom($room)->create();

    expect($room->sectionSchedules)->toHaveCount(1);
});
