<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'create-post',
            'edit-post',
            'edit-any-post',
            'delete-post',
            'delete-any-post',
            'publish-post',
            'manage-users',
            'manage-roles',
            'manage-newsletters',
            'manage-subscriptions',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $adminRole = Role::findOrCreate('admin');
        $adminRole->givePermissionTo(Permission::all());

        $editorRole = Role::findOrCreate('editor');
        $editorRole->givePermissionTo([
            'create-post',
            'edit-any-post',
            'delete-any-post',
            'publish-post',
        ]);

        $authorRole = Role::findOrCreate('author');
        $authorRole->givePermissionTo([
            'create-post',
            'edit-post',
            'delete-post',
            'publish-post',
        ]);

        Role::findOrCreate('subscriber');
    }
}
