<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'clients.view',
            'clients.manage',
            'applications.view',
            'applications.manage',
            'subscriptions.view',
            'subscriptions.manage',
            'invoices.view',
            'invoices.manage',
            'payments.view',
            'payments.verify',
            'gateway.sync',
            'users.manage',
            'dashboard.view',
            'monitoring.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roleMap = [
            'Super Admin' => $permissions,
            'Administrator' => [
                'clients.view', 'clients.manage',
                'applications.view', 'applications.manage',
                'subscriptions.view', 'subscriptions.manage',
                'gateway.sync',
                'dashboard.view',
                'monitoring.view',
            ],
            'Finance' => [
                'clients.view',
                'applications.view',
                'subscriptions.view',
                'invoices.view', 'invoices.manage',
                'payments.view', 'payments.verify',
                'dashboard.view',
                'monitoring.view',
            ],
            'Viewer' => [
                'clients.view',
                'applications.view',
                'subscriptions.view',
                'invoices.view',
                'payments.view',
                'dashboard.view',
                'monitoring.view',
            ],
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
