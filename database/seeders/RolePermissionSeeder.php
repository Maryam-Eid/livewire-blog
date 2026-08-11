<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'create-post',
            'edit-any-post',
            'delete-any-post',
            'publish-post'
        ]);

        $authorRole = Role::create(['name' => 'author']);
        $authorRole->givePermissionTo([
            'create-post',
            'edit-post',
            'delete-post',
        ]);

        Role::create(['name' => 'subscriber']);
    }
}
