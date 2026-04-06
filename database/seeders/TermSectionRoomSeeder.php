<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Enums\RoomType;
use App\Models\Course;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class TermSectionRoomSeeder extends Seeder
{
    /**
     * Seed terms, rooms, sections, and section schedules.
     */
    public function run(): void
    {
        $terms = $this->seedTerms();
        $rooms = $this->seedRooms();
        $this->seedSections($terms['FA2026'], $rooms);
    }

    /** @return array<string, Term> */
    private function seedTerms(): array
    {
        $data = [
            'FA2026' => ['name' => 'Fall 2026', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15', 'registration_start' => '2026-08-01', 'registration_end' => '2026-08-25'],
            'SP2027' => ['name' => 'Spring 2027', 'start_date' => '2027-01-15', 'end_date' => '2027-05-15', 'registration_start' => '2026-12-01', 'registration_end' => '2027-01-10'],
            'SU2027' => ['name' => 'Summer 2027', 'start_date' => '2027-06-01', 'end_date' => '2027-08-15', 'registration_start' => '2027-05-01', 'registration_end' => '2027-05-25'],
        ];

        $terms = [];

        foreach ($data as $code => $attrs) {
            $terms[$code] = Term::create([
                'code' => $code,
                ...$attrs,
            ]);
        }

        return $terms;
    }

    /** @return array<string, Room> */
    private function seedRooms(): array
    {
        $data = [
            'CR-101' => ['name' => 'Classroom 101', 'building' => 'Main Building', 'capacity' => 30, 'type' => RoomType::Classroom],
            'CR-102' => ['name' => 'Classroom 102', 'building' => 'Main Building', 'capacity' => 30, 'type' => RoomType::Classroom],
            'WS-A' => ['name' => 'Welding Shop A', 'building' => 'Trade Building', 'capacity' => 20, 'type' => RoomType::Shop],
            'WS-B' => ['name' => 'Welding Shop B', 'building' => 'Trade Building', 'capacity' => 20, 'type' => RoomType::Shop],
            'AS-A' => ['name' => 'Auto Shop A', 'building' => 'Trade Building', 'capacity' => 15, 'type' => RoomType::Shop],
            'EL-A' => ['name' => 'Electrical Lab A', 'building' => 'Trade Building', 'capacity' => 20, 'type' => RoomType::Lab],
            'PL-A' => ['name' => 'Plumbing Lab A', 'building' => 'Trade Building', 'capacity' => 18, 'type' => RoomType::Lab],
            'CL-101' => ['name' => 'Computer Lab', 'building' => 'Main Building', 'capacity' => 25, 'type' => RoomType::Lab],
        ];

        $rooms = [];

        foreach ($data as $code => $attrs) {
            $rooms[$code] = Room::create([
                'code' => $code,
                'description' => "{$attrs['name']} in {$attrs['building']}.",
                ...$attrs,
            ]);
        }

        return $rooms;
    }

    /** @param array<string, Room> $rooms */
    private function seedSections(Term $fallTerm, array $rooms): void
    {
        $instructor = User::where('email', 'instructor@example.com')->first();

        $sectionData = [
            ['course' => 'WLD-101', 'number' => '01', 'max' => 20, 'instructor' => $instructor, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '08:00', 'end' => '09:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Wednesday, 'start' => '08:00', 'end' => '09:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Tuesday, 'start' => '08:00', 'end' => '10:00', 'room' => 'WS-A'],
                ['day' => DayOfWeek::Thursday, 'start' => '08:00', 'end' => '10:00', 'room' => 'WS-A'],
            ]],
            ['course' => 'WLD-102', 'number' => '01', 'max' => 20, 'instructor' => $instructor, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Wednesday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Tuesday, 'start' => '10:00', 'end' => '12:00', 'room' => 'WS-A'],
                ['day' => DayOfWeek::Thursday, 'start' => '10:00', 'end' => '12:00', 'room' => 'WS-A'],
            ]],
            ['course' => 'WLD-201', 'number' => '01', 'max' => 20, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '13:00', 'end' => '14:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Wednesday, 'start' => '13:00', 'end' => '14:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Tuesday, 'start' => '13:00', 'end' => '15:00', 'room' => 'WS-B'],
                ['day' => DayOfWeek::Thursday, 'start' => '13:00', 'end' => '15:00', 'room' => 'WS-B'],
            ]],
            ['course' => 'WLD-101', 'number' => '02', 'max' => 20, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Tuesday, 'start' => '15:00', 'end' => '16:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Thursday, 'start' => '15:00', 'end' => '16:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Saturday, 'start' => '08:00', 'end' => '10:00', 'room' => 'WS-B'],
            ]],
            ['course' => 'HVAC-101', 'number' => '01', 'max' => 25, 'instructor' => $instructor, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '13:00', 'end' => '14:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Wednesday, 'start' => '13:00', 'end' => '14:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Friday, 'start' => '08:00', 'end' => '10:00', 'room' => 'EL-A'],
            ]],
            ['course' => 'HVAC-102', 'number' => '01', 'max' => 25, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Tuesday, 'start' => '08:00', 'end' => '09:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Thursday, 'start' => '08:00', 'end' => '09:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Friday, 'start' => '10:00', 'end' => '12:00', 'room' => 'EL-A'],
            ]],
            ['course' => 'ELEC-101', 'number' => '01', 'max' => 25, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Wednesday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Friday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-102'],
            ]],
            ['course' => 'ELEC-102', 'number' => '01', 'max' => 20, 'instructor' => $instructor, 'schedules' => [
                ['day' => DayOfWeek::Tuesday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Thursday, 'start' => '10:00', 'end' => '11:00', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Tuesday, 'start' => '13:00', 'end' => '15:00', 'room' => 'EL-A'],
                ['day' => DayOfWeek::Thursday, 'start' => '13:00', 'end' => '15:00', 'room' => 'EL-A'],
            ]],
            ['course' => 'PLMB-101', 'number' => '01', 'max' => 18, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '14:30', 'end' => '16:00', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Wednesday, 'start' => '14:30', 'end' => '16:00', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Friday, 'start' => '13:00', 'end' => '15:00', 'room' => 'PL-A'],
            ]],
            ['course' => 'PLMB-102', 'number' => '01', 'max' => 18, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Tuesday, 'start' => '14:30', 'end' => '15:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Thursday, 'start' => '14:30', 'end' => '15:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Friday, 'start' => '15:00', 'end' => '17:00', 'room' => 'PL-A'],
            ]],
            ['course' => 'AUTO-101', 'number' => '01', 'max' => 15, 'instructor' => $instructor, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '08:00', 'end' => '09:00', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Wednesday, 'start' => '08:00', 'end' => '09:00', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Tuesday, 'start' => '08:00', 'end' => '10:00', 'room' => 'AS-A'],
                ['day' => DayOfWeek::Thursday, 'start' => '08:00', 'end' => '10:00', 'room' => 'AS-A'],
            ]],
            ['course' => 'AUTO-102', 'number' => '01', 'max' => 15, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '11:00', 'end' => '12:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Wednesday, 'start' => '11:00', 'end' => '12:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Tuesday, 'start' => '10:00', 'end' => '12:00', 'room' => 'AS-A'],
                ['day' => DayOfWeek::Thursday, 'start' => '10:00', 'end' => '12:00', 'room' => 'AS-A'],
            ]],
            ['course' => 'HVAC-201', 'number' => '01', 'max' => 25, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '15:00', 'end' => '16:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Wednesday, 'start' => '15:00', 'end' => '16:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Friday, 'start' => '13:00', 'end' => '15:00', 'room' => 'EL-A'],
            ]],
            ['course' => 'ELEC-201', 'number' => '01', 'max' => 20, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '16:00', 'end' => '17:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Wednesday, 'start' => '16:00', 'end' => '17:30', 'room' => 'CR-101'],
                ['day' => DayOfWeek::Tuesday, 'start' => '15:00', 'end' => '17:00', 'room' => 'EL-A'],
                ['day' => DayOfWeek::Thursday, 'start' => '15:00', 'end' => '17:00', 'room' => 'EL-A'],
            ]],
            ['course' => 'AUTO-201', 'number' => '01', 'max' => 15, 'instructor' => null, 'schedules' => [
                ['day' => DayOfWeek::Monday, 'start' => '14:30', 'end' => '15:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Wednesday, 'start' => '14:30', 'end' => '15:30', 'room' => 'CR-102'],
                ['day' => DayOfWeek::Friday, 'start' => '10:00', 'end' => '12:00', 'room' => 'AS-A'],
            ]],
        ];

        foreach ($sectionData as $data) {
            $course = Course::where('code', $data['course'])->first();

            if (! $course) {
                continue;
            }

            $section = Section::create([
                'course_id' => $course->id,
                'term_id' => $fallTerm->id,
                'instructor_id' => $data['instructor']?->id,
                'section_number' => $data['number'],
                'max_enrollment' => $data['max'],
            ]);

            foreach ($data['schedules'] as $schedule) {
                SectionSchedule::create([
                    'section_id' => $section->id,
                    'room_id' => $rooms[$schedule['room']]->id,
                    'day_of_week' => $schedule['day'],
                    'start_time' => $schedule['start'],
                    'end_time' => $schedule['end'],
                ]);
            }
        }
    }
}
