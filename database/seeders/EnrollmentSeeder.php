<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Seed enrollment data for Fall 2026.
     */
    public function run(): void
    {
        $fallTerm = Term::where('code', 'FA2026')->first();

        if (! $fallTerm) {
            return;
        }

        $student = User::where('email', 'student@example.com')->first();
        $testUser = User::where('email', 'test@example.com')->first();

        if ($student) {
            $this->enrollStudent($student, $fallTerm, [
                ['course' => 'WLD-101', 'section' => '01'],
                ['course' => 'WLD-102', 'section' => '01'],
                ['course' => 'HVAC-101', 'section' => '01'],
            ]);
        }

        if ($testUser) {
            $this->enrollStudent($testUser, $fallTerm, [
                ['course' => 'ELEC-101', 'section' => '01'],
                ['course' => 'AUTO-101', 'section' => '01'],
            ]);

            // Create a completed enrollment for WLD-101 (enables prerequisite testing for WLD-201)
            $wld101Section = Section::query()
                ->whereHas('course', fn ($q) => $q->where('code', 'WLD-101'))
                ->where('term_id', $fallTerm->id)
                ->where('section_number', '02')
                ->first();

            if ($wld101Section) {
                Enrollment::create([
                    'user_id' => $testUser->id,
                    'section_id' => $wld101Section->id,
                    'status' => EnrollmentStatus::Completed,
                    'enrolled_at' => now()->subMonths(6),
                ]);
            }
        }
    }

    /** @param array<int, array{course: string, section: string}> $sections */
    private function enrollStudent(User $student, Term $term, array $sections): void
    {
        foreach ($sections as $data) {
            $course = Course::where('code', $data['course'])->first();

            if (! $course) {
                continue;
            }

            $section = Section::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('section_number', $data['section'])
                ->first();

            if (! $section) {
                continue;
            }

            Enrollment::create([
                'user_id' => $student->id,
                'section_id' => $section->id,
                'status' => EnrollmentStatus::Enrolled,
                'enrolled_at' => now(),
            ]);
        }
    }
}
