<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Get authenticated user's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['clientProfile', 'assignedTasks']);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'profile_image_link' => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
        ]);

        $user = $this->userService->update($user, $validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Get user's activity summary.
     */
    public function activitySummary(Request $request): JsonResponse
    {
        $summary = $this->userService->getActivitySummary($request->user());

        return response()->json([
            'summary' => $summary,
        ]);
    }
}
