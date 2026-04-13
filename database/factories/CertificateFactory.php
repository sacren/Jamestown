<?php

namespace Database\Factories;

use App\Actions\Documents\GenerateCertificateNumber;
use App\Models\Certificate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Certificate> */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'program_id' => ProgramFactory::new(),
            'certificate_number' => fn () => 'CERT-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'issued_at' => now(),
            'issued_by' => UserFactory::new(),
        ];
    }

    public function forStudent(User $student): static
    {
        return $this->state(fn () => ['user_id' => $student->id]);
    }

    public function forProgram(Program $program): static
    {
        return $this->state(fn () => ['program_id' => $program->id]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
            'revoked_by' => UserFactory::new(),
        ]);
    }
}
