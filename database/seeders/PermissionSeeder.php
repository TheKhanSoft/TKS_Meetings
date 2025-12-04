<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $roles = ['Super Admin', 'VC', 'Registrar', 'Director', 'Dean', 'Faculty', 'Staff'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Define entities for CRUD permissions
        $entities = [
            'users',
            'roles',
            'permissions',
            'positions',
            'employment_statuses',
            'meetings',
            'meeting_types',
            'agenda_items',
            'agenda_item_types',
            'minutes',
            'announcements',
            'help_articles',
            'help_categories',
            'notifications',
            'participants',
            'keywords',
        ];

        // Define standard actions
        $actions = [
            'view',
            'create',
            'edit',
            'delete',
            'restore',
            'force_delete',
            'export',
            'import',
        ];

        $permissions = [];

        // Generate CRUD permissions
        foreach ($entities as $entity) {
            $readableEntity = str_replace('_', ' ', $entity);
            foreach ($actions as $action) {
                $permissions[] = "{$action} {$readableEntity}";
            }
        }

        // Add specific permissions
        $specificPermissions = [
            'view settings',
            'edit settings',
            'bypass maintenance',
            'view dashboard',
            'assign positions',
            'assign permissions',
            
            // Meeting Specifics
            'download minutes',
            'view minutes pdf',
            'download agenda',
            'view agenda pdf',
            'finalize meetings',
        ];

        $permissions = array_merge($permissions, $specificPermissions);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        // Assign Permissions to Roles
        $rolePermissions = [
            'VC' => [
                'view meetings', 'view agenda items', 'view minutes', 
                'view users', 'view positions', 'view employment statuses',
                'view announcements', 'view notifications', 'view dashboard'
            ],
            'Registrar' => [
                'view meetings', 'create meetings', 'edit meetings', 
                'view agenda items', 'create agenda items', 
                'view minutes', 'view dashboard',
                'view users', 'create users', 'edit users',
                'view positions', 'create positions', 'edit positions',
                'view employment statuses', 'create employment statuses', 'edit employment statuses',
                'view announcements', 'create announcements',
                'view notifications', 'create notifications'
            ],
            'Director' => ['view meetings', 'view agenda items', 'view minutes', 'view announcements', 'view dashboard'],
            'Dean' => ['view meetings', 'view minutes', 'view announcements', 'view dashboard'],
            'Faculty' => ['view meetings', 'view announcements', 'view dashboard'],
            'Staff' => ['view announcements', 'view dashboard'],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->syncPermissions($perms);
            }
        }
    }
}
