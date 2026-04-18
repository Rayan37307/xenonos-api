<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    /**
     * List all roles.
     */
    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAll();

        return response()->json([
            'roles' => RoleResource::collection($roles),
            'total' => $roles->count(),
        ]);
    }

    /**
     * Get a single role.
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->findById($id);

        return response()->json([
            'role' => new RoleResource($role),
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated());

        return response()->json([
            'message' => 'Role created successfully',
            'role' => new RoleResource($role),
        ], 201);
    }

    /**
     * Update a role.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->findById($id);
        $role = $this->roleService->update($role, $request->validated());

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => new RoleResource($role),
        ]);
    }

    /**
     * Update role permissions.
     */
    public function updatePermissions(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = $this->roleService->findById($id);
        $role = $this->roleService->updatePermissions($role, $validated['permissions']);

        return response()->json([
            'message' => 'Permissions updated successfully',
            'role' => new RoleResource($role),
        ]);
    }

    /**
     * Delete a role.
     */
    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleService->findById($id);
        $this->roleService->delete($role);

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Duplicate a role.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $role = $this->roleService->findById($id);
        $newRole = $this->roleService->duplicate($role, $validated['name']);

        return response()->json([
            'message' => 'Role duplicated successfully',
            'role' => new RoleResource($newRole),
        ], 201);
    }

    /**
     * Assign users to a role.
     */
    public function assignUsers(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $role = $this->roleService->findById($id);
        $count = $this->roleService->assignUsers($role, $validated['user_ids']);

        return response()->json([
            'message' => 'Users assigned successfully',
            'assigned_count' => $count,
            'role' => new RoleResource($role->fresh()->load('users')),
        ]);
    }

    /**
     * Unassign users from a role.
     */
    public function unassignUsers(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $role = $this->roleService->findById($id);
        $count = $this->roleService->unassignUsers($role, $validated['user_ids']);

        return response()->json([
            'message' => 'Users unassigned successfully',
            'unassigned_count' => $count,
            'role' => new RoleResource($role->fresh()->load('users')),
        ]);
    }

    /**
     * Get activity log for a role.
     */
    public function activityLog(Request $request, int $id): JsonResponse
    {
        $role = $this->roleService->findById($id);
        $perPage = (int) $request->query('per_page', 20);
        $logs = $this->roleService->getActivityLog($role, $perPage);

        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'message' => $log->description,
                    'causer_name' => $log->causer?->name ?? 'System',
                    'created_at' => $log->created_at?->diffForHumans(),
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get all available permissions grouped by module.
     */
    public function permissions(): JsonResponse
    {
        $groups = $this->roleService->getAllPermissionsGrouped();

        return response()->json([
            'permissions' => $groups,
        ]);
    }
}
