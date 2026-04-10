<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
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
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // Create client role
        $client = Role::firstOrCreate(['name' => 'client']);
        $client->syncPermissions([
            'view-analytics',
            'send-messages',
        ]);

        // Create worker role
        $worker = Role::firstOrCreate(['name' => 'worker']);
        $worker->syncPermissions([
            'send-messages',
            'view-analytics',
        ]);

        // Create developer role
        $developer = Role::firstOrCreate(['name' => 'developer']);
        $developer->syncPermissions([
            'view-all-projects',
            'manage-projects',
            'create-projects',
            'edit-projects',
            'view-all-tasks',
            'manage-tasks',
            'create-tasks',
            'edit-tasks',
            'assign-tasks',
            'send-messages',
            'view-analytics',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Roles: admin, client, worker, developer');
    }
}
