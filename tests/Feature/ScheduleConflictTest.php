<?php

use App\Concerns\ChecksScheduleConflicts;
use App\Enums\DayOfWeek;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->checker = new class
    {
        use ChecksScheduleConflicts;

        public function roomConflict(...$args): bool
        {
            return $this->checkRoomConflict(...$args);
        }

        public function instructorConflict(...$args): bool
        {
            return $this->checkInstructorConflict(...$args);
        }
    };
});

test('detects room conflict when times overlap', function () {
    $term = Term::factory()->create();
    $room = Room::factory()->create();
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->forRoom($room)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $hasConflict = $this->checker->roomConflict(
        $term->id, $room->id, 'monday', '10:00', '11:00'
    );

    expect($hasConflict)->toBeTrue();
});

test('detects instructor conflict when times overlap', function () {
    $term = Term::factory()->create();
    $instructor = User::factory()->asInstructor()->create();
    $section = Section::factory()->forTerm($term)->withInstructor($instructor)->create();
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Tuesday,
        'start_time' => '13:00',
        'end_time' => '14:30',
    ]);

    $hasConflict = $this->checker->instructorConflict(
        $term->id, $instructor->id, 'tuesday', '14:00', '15:00'
    );

    expect($hasConflict)->toBeTrue();
});

test('no conflict when times do not overlap', function () {
    $term = Term::factory()->create();
    $room = Room::factory()->create();
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->forRoom($room)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $hasConflict = $this->checker->roomConflict(
        $term->id, $room->id, 'monday', '10:30', '12:00'
    );

    expect($hasConflict)->toBeFalse();
});

test('no conflict when different days', function () {
    $term = Term::factory()->create();
    $room = Room::factory()->create();
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->forRoom($room)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $hasConflict = $this->checker->roomConflict(
        $term->id, $room->id, 'tuesday', '09:00', '10:30'
    );

    expect($hasConflict)->toBeFalse();
});

test('no conflict when different rooms', function () {
    $term = Term::factory()->create();
    $room1 = Room::factory()->create();
    $room2 = Room::factory()->create();
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->forRoom($room1)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $hasConflict = $this->checker->roomConflict(
        $term->id, $room2->id, 'monday', '09:00', '10:30'
    );

    expect($hasConflict)->toBeFalse();
});

test('excludes specified section from conflict check', function () {
    $term = Term::factory()->create();
    $room = Room::factory()->create();
    $section = Section::factory()->forTerm($term)->create();
    SectionSchedule::factory()->forSection($section)->forRoom($room)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $hasConflict = $this->checker->roomConflict(
        $term->id, $room->id, 'monday', '09:00', '10:30', $section->id
    );

    expect($hasConflict)->toBeFalse();
});
