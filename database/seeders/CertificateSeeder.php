<?php

namespace Database\Seeders;

use App\Actions\Documents\IssueCertificate;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::where('code', 'FA2026')->first();
        if (! $term) {
            return;
        }

        $issuer = User::where('email', 'registrar@example.com')->first()
            ?? User::where('email', 'admin@example.com')->first();

        if (! $issuer) {
            return;
        }

        $this->seedGraduateStudent($term, $issuer);
        $this->seedRevokedGrad($term, $issuer);
        $this->seedNearGradStudent($term);
    }

    private function seedGraduateStudent(Term $term, User $issuer): void
    {
        $program = Program::where('code', 'PLMB')->first();
        if (! $program) {
            return;
        }

        $student = $this->createStudent('Graduate Student', 'graduate@example.com');

        $this->ensureSectionsExist($program, $term);
        $this->enrollAndComplete($student, $program, $term);

        app(IssueCertificate::class)->handle($student, $program, $issuer);
    }

    private function seedRevokedGrad(Term $term, User $issuer): void
    {
        $program = Program::where('code', 'WLD')->first();
        if (! $program) {
            return;
        }

        $student = $this->createStudent('Revoked Grad', 'revokedgrad@example.com');

        $this->ensureSectionsExist($program, $term);
        $this->enrollAndComplete($student, $program, $term);

        $certificate = app(IssueCertificate::class)->handle($student, $program, $issuer);
        $certificate->update([
            'revoked_at' => now(),
            'revoked_by' => $issuer->id,
            'notes' => 'Academic integrity violation',
        ]);
    }

    private function seedNearGradStudent(Term $term): void
    {
        $program = Program::where('code', 'HVAC')->first();
        if (! $program) {
            return;
        }

        $student = $this->createStudent('Near-Grad Student', 'neargrad@example.com');

        $this->ensureSectionsExist($program, $term);

        $courses = $program->courses()->orderBy('code')->get();
        $completedCount = 0;

        foreach ($courses as $course) {
            $section = Section::where('course_id', $course->id)->where('term_id', $term->id)->first();
            if (! $section) {
                continue;
            }

            if ($completedCount < 3) {
                Enrollment::create([
                    'user_id' => $student->id,
                    'section_id' => $section->id,
                    'status' => EnrollmentStatus::Completed,
                    'enrolled_at' => $term->start_date,
                    'completed_at' => now(),
                ]);
                $completedCount++;
            } else {
                Enrollment::create([
                    'user_id' => $student->id,
                    'section_id' => $section->id,
                    'status' => EnrollmentStatus::Enrolled,
                    'enrolled_at' => $term->start_date,
                ]);
            }
        }
    }

    private function createStudent(string $name, string $email): User
    {
        $user = User::factory()->asStudent()->create([
            'name' => $name,
            'email' => $email,
        ]);

        return $user;
    }

    private function ensureSectionsExist(Program $program, Term $term): void
    {
        $courses = $program->courses;

        foreach ($courses as $course) {
            $exists = Section::where('course_id', $course->id)->where('term_id', $term->id)->exists();
            if (! $exists) {
                Section::create([
                    'course_id' => $course->id,
                    'term_id' => $term->id,
                    'section_number' => '01',
                    'max_enrollment' => 20,
                ]);
            }
        }
    }

    private function enrollAndComplete(User $student, Program $program, Term $term): void
    {
        $courses = $program->courses;

        foreach ($courses as $course) {
            $section = Section::where('course_id', $course->id)->where('term_id', $term->id)->first();
            if (! $section) {
                continue;
            }

            Enrollment::create([
                'user_id' => $student->id,
                'section_id' => $section->id,
                'status' => EnrollmentStatus::Completed,
                'enrolled_at' => $term->start_date,
                'completed_at' => now(),
            ]);
        }
    }
}
