<?php

namespace App\Http\Controllers\Api\Session;

use App\Http\Controllers\Controller;
use App\Services\UserSessionService;
use App\Http\Resources\UserSessionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(
        private UserSessionService $sessionService
    ) {}

    /**
     * Get all user sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = $this->sessionService->getUserSessions($request->user());

        return response()->json([
            'sessions' => UserSessionResource::collection($sessions),
        ]);
    }

    /**
     * Get current session.
     */
    public function current(Request $request): JsonResponse
    {
        $session = $this->sessionService->getCurrentSession($request->user());

        if (!$session) {
            return response()->json([
                'message' => 'No current session found',
            ], 404);
        }

        return response()->json([
            'session' => new UserSessionResource($session),
        ]);
    }

    /**
     * Revoke a specific session.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $success = $this->sessionService->deleteSession($request->user(), $id);

        if (!$success) {
            return response()->json([
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Session revoked successfully',
        ]);
    }

    /**
     * Revoke all other sessions (keep current).
     */
    public function revokeOthers(Request $request): JsonResponse
    {
        $deleted = $this->sessionService->deleteAllOtherSessions($request->user());

        return response()->json([
            'message' => 'All other sessions revoked successfully',
            'revoked_count' => $deleted,
        ]);
    }

    /**
     * Revoke all sessions (logout from all devices).
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $deleted = $this->sessionService->deleteAllSessions($request->user());

        // Revoke current token as well
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'All sessions revoked successfully. You will be logged out.',
            'revoked_count' => $deleted,
        ]);
    }
}
