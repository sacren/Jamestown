<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Create the user as a student with a student profile.
     */
    public function asStudent(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(Role::Student);
            $user->studentProfile()->create(
                StudentProfileFactory::new()->definition()
            );
        });
    }

    /**
     * Create the user as an instructor with an instructor profile.
     */
    public function asInstructor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(Role::Instructor);
            $user->instructorProfile()->create(
                InstructorProfileFactory::new()->definition()
            );
        });
    }

    /**
     * Create the user as an admin with a staff profile.
     */
    public function asAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(Role::Admin);
            $user->staffProfile()->create(
                StaffProfileFactory::new()->definition()
            );
        });
    }

    /**
     * Create the user as a registrar with a staff profile.
     */
    public function asRegistrar(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(Role::Registrar);
            $user->staffProfile()->create(
                StaffProfileFactory::new()->definition()
            );
        });
    }

    /**
     * Create the user as a super admin with a staff profile.
     */
    public function asSuperAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(Role::SuperAdmin);
            $user->staffProfile()->create(
                StaffProfileFactory::new()->definition()
            );
        });
    }
}
