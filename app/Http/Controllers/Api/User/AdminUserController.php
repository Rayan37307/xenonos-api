<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * List all users (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['role', 'search']);
        $users = $this->userService->getAll($filters);

        return response()->json([
            'users' => UserResource::collection($users),
        ]);
    }

    /**
     * Get user by ID (admin only).
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update user (admin only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getById($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'in:admin,client,worker'],
            'company_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
        ]);

        $user = $this->userService->update($user, $validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Delete user (admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);
        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get all workers.
     */
    public function workers(): JsonResponse
    {
        $workers = $this->userService->getAllWorkers();

        return response()->json([
            'workers' => UserResource::collection($workers),
        ]);
    }

    /**
     * Get all clients.
     */
    public function clients(): JsonResponse
    {
        $clients = $this->userService->getAllClients();

        return response()->json([
            'clients' => UserResource::collection($clients),
        ]);
    }
}
