<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User permissions
            'view-all-users',
            'manage-users',
            
            // Project permissions
            'view-all-projects',
            'manage-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            
            // Task permissions
            'view-all-tasks',
            'manage-tasks',
            'create-tasks',
            'edit-tasks',
            'delete-tasks',
            'assign-tasks',
            
            // Analytics permissions
            'view-analytics',
            'view-revenue',
            
            // Chat permissions
            'send-messages',
            'view-all-messages',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Create admin role
        $admin = Role::firstOrCreate(['name' => 'admin'], [
            'guard_name' => 'web',
            'description' => 'Full system access and administrative privileges',
            'status' => 'active',
        ]);
        $admin->syncPermissions(Permission::all());

        // Create client role
        $client = Role::firstOrCreate(['name' => 'client'], [
            'guard_name' => 'web',
            'description' => 'View projects and communicate with team',
            'status' => 'active',
        ]);
        $client->syncPermissions([
            'view-analytics',
            'send-messages',
        ]);

        // Create worker role
        $worker = Role::firstOrCreate(['name' => 'worker'], [
            'guard_name' => 'web',
            'description' => 'Task execution and project collaboration',
            'status' => 'active',
        ]);
        $worker->syncPermissions([
            'send-messages',
            'view-analytics',
        ]);

        // Create developer role with full access
        $developer = Role::firstOrCreate(['name' => 'developer'], [
            'guard_name' => 'web',
            'description' => 'Full access for development team',
            'status' => 'active',
        ]);
        $developer->syncPermissions(Permission::all());

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Roles: admin, client, worker, developer');
    }
}
