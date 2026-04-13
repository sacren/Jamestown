<?php

namespace App\Actions\Documents;

use App\Models\Certificate;
use App\Models\Program;
use App\Models\User;
use InvalidArgumentException;

class IssueCertificate
{
    public function __construct(
        private CheckProgramCompletion $completionChecker,
        private GenerateCertificateNumber $numberGenerator,
    ) {}

    public function handle(User $student, Program $program, User $issuer): Certificate
    {
        $existing = Certificate::query()
            ->where('user_id', $student->id)
            ->where('program_id', $program->id)
            ->exists();

        if ($existing) {
            throw new InvalidArgumentException('A certificate already exists for this student and program.');
        }

        $result = $this->completionChecker->handle($student, $program);

        if (! $result['eligible']) {
            throw new InvalidArgumentException('Student has not completed all courses in this program.');
        }

        return Certificate::create([
            'user_id' => $student->id,
            'program_id' => $program->id,
            'certificate_number' => $this->numberGenerator->handle(),
            'issued_at' => now(),
            'issued_by' => $issuer->id,
        ]);
    }
}
