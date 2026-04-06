<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            ProgramAndCourseSeeder::class,
        ]);

        User::factory()->asSuperAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->asAdmin()->create([
            'name' => 'School Admin',
            'email' => 'schooladmin@example.com',
        ]);

        User::factory()->asRegistrar()->create([
            'name' => 'Registrar',
            'email' => 'registrar@example.com',
        ]);

        User::factory()->asInstructor()->create([
            'name' => 'Instructor',
            'email' => 'instructor@example.com',
        ]);

        User::factory()->asStudent()->create([
            'name' => 'Student',
            'email' => 'student@example.com',
        ]);

        User::factory()->asStudent()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            TermSectionRoomSeeder::class,
        ]);
    }
}
