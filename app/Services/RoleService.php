<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    /**
     * Get all roles with user counts.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAll()
    {
        $roles = Role::where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->get();

        return $roles->map(function ($role) {
            $role->users_list = $role->users()
                ->select('id', 'name', 'email', 'avatar', 'role')
                ->limit(10)
                ->get();

            return $role;
        });
    }

    /**
     * Get a single role with details.
     *
     * @param int $id
     * @return Role|null
     */
    public function findById(int $id): ?Role
    {
        return Role::where('guard_name', 'web')
            ->with('permissions')
            ->with('users:id,name,email,avatar,role')
            ->findOrFail($id);
    }

    /**
     * Create a new role with permissions.
     *
     * @param array $data
     * @return Role
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissions = Permission::whereIn('name', $data['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            // Assign users to this role if provided
            if (isset($data['user_ids']) && is_array($data['user_ids'])) {
                User::whereIn('id', $data['user_ids'])->update(['role' => $data['name']]);
            }

            return $role;
        });
    }

    /**
     * Update a role.
     *
     * @param Role $role
     * @param array $data
     * @return Role
     */
    public function update(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'] ?? $role->name,
            'description' => $data['description'] ?? $role->description,
            'status' => $data['status'] ?? $role->status,
        ]);

        // If role name changed, update users.role column
        if (isset($data['name']) && $data['name'] !== $role->getOriginal('name')) {
            User::where('role', $role->getOriginal('name'))->update(['role' => $data['name']]);
        }

        return $role->fresh();
    }

    /**
     * Update role permissions.
     *
     * @param Role $role
     * @param array $permissions
     * @return Role
     */
    public function updatePermissions(Role $role, array $permissions): Role
    {
        $permissionModels = Permission::whereIn('name', $permissions)->get();
        $role->syncPermissions($permissionModels);

        return $role->fresh()->load('permissions');
    }

    /**
     * Delete a role.
     *
     * @param Role $role
     * @return bool
     */
    public function delete(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            // Reset role for users with this role
            User::where('role', $role->name)->update(['role' => 'worker']);

            // Remove spatie role assignments
            $role->users()->detach();

            return $role->delete();
        });
    }

    /**
     * Duplicate a role.
     *
     * @param Role $role
     * @param string $newName
     * @return Role
     */
    public function duplicate(Role $role, string $newName): Role
    {
        return DB::transaction(function () use ($role, $newName) {
            $newRole = Role::create([
                'name' => $newName,
                'guard_name' => 'web',
                'description' => 'Copy of ' . ($role->description ?? $role->name),
                'status' => 'active',
            ]);

            $newRole->syncPermissions($role->permissions);

            return $newRole->load('permissions');
        });
    }

    /**
     * Assign users to a role.
     *
     * @param Role $role
     * @param array $userIds
     * @return int
     */
    public function assignUsers(Role $role, array $userIds): int
    {
        return User::whereIn('id', $userIds)->update(['role' => $role->name]);
    }

    /**
     * Unassign users from a role.
     *
     * @param Role $role
     * @param array $userIds
     * @return int
     */
    public function unassignUsers(Role $role, array $userIds): int
    {
        return User::whereIn('id', $userIds)
            ->where('role', $role->name)
            ->update(['role' => 'worker']);
    }

    /**
     * Get all available permissions grouped by module.
     *
     * @return array
     */
    public function getAllPermissionsGrouped(): array
    {
        $permissions = Permission::all();

        $groups = [];
        foreach ($permissions as $permission) {
            $parts = explode('-', $permission->name);
            $module = end($parts);

            if (!isset($groups[$module])) {
                $groups[$module] = [
                    'module' => $module,
                    'permissions' => [],
                ];
            }
            $groups[$module]['permissions'][] = [
                'name' => $permission->name,
                'label' => $permission->name,
            ];
        }

        return array_values($groups);
    }

    /**
     * Get activity log for a role.
     *
     * @param Role $role
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getActivityLog(Role $role, int $perPage = 20): LengthAwarePaginator
    {
        return \Spatie\Activitylog\Models\Activity::where('subject_type', Role::class)
            ->where('subject_id', $role->id)
            ->with('causer')
            ->latest()
            ->paginate($perPage);
    }
}
