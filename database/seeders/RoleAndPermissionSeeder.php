<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage-users',
            'manage-programs',
            'manage-courses',
            'manage-enrollments',
            'manage-grades',
            'manage-attendance',
            'manage-payments',
            'view-reports',
            'manage-announcements',
            'manage-terms',
            'manage-rooms',
            'manage-sections',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign permissions
        // Super Admin gets all permissions via Gate::before (spatie convention)
        SpatieRole::findOrCreate(Role::SuperAdmin->value);

        SpatieRole::findOrCreate(Role::Admin->value)
            ->givePermissionTo($permissions);

        SpatieRole::findOrCreate(Role::Registrar->value)
            ->givePermissionTo(['manage-users', 'manage-enrollments', 'view-reports', 'manage-terms', 'manage-sections']);

        SpatieRole::findOrCreate(Role::Instructor->value)
            ->givePermissionTo(['manage-grades', 'manage-attendance']);

        SpatieRole::findOrCreate(Role::Student->value);
    }
}
