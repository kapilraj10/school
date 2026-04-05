<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::allPermissions() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $volunteer = Role::findOrCreate('volunteer', 'web');

        $allPermissions = Permission::query()->pluck('name')->all();

        $superAdmin->syncPermissions($allPermissions);
        $admin->syncPermissions($allPermissions);

        $volunteer->syncPermissions([
            'dashboard.view',
            'timetable_designer.view',
            'user.list',
            'user.view',
            'class_room.list',
            'class_room.view',
            'teacher.list',
            'teacher.view',
            'subject.list',
            'subject.view',
            'academic_term.list',
            'academic_term.view',
            'holiday.list',
            'holiday.view',
            'combined_period.list',
            'combined_period.view',
            'class_subject_setting.list',
            'class_subject_setting.view',
            'timetable_setting.list',
            'timetable_setting.view',
        ]);

        $adminUser = User::query()->where('email', 'admin@admin.com')->first();

        if ($adminUser) {
            $adminUser->syncRoles(['super-admin']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
