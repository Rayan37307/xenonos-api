<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SignupInviteService;
use App\Models\SignupInvite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SignupInviteController extends Controller
{
    public function __construct(
        private SignupInviteService $signupInviteService
    ) {}

    /**
     * Create a new signup invite (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $expiresInHours = $validated['expires_in_hours'] ?? null;

        $invite = $this->signupInviteService->createInvite(
            $request->user()->id,
            $expiresInHours
        );

        return response()->json([
            'message' => 'Signup invite created successfully',
            'invite' => [
                'id' => $invite->id,
                'token' => $invite->token,
                'signup_url' => $invite->signup_url,
                'expires_at' => $invite->expires_at,
                'created_at' => $invite->created_at,
                'creator' => [
                    'id' => $invite->creator->id,
                    'name' => $invite->creator->name,
                    'email' => $invite->creator->email,
                ],
            ],
        ], 201);
    }

    /**
     * List all signup invites (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $request->query('filter');

        $invites = match ($filter) {
            'valid' => $this->signupInviteService->getValidInvites(),
            'used' => $this->signupInviteService->getUsedInvites(),
            'expired' => $this->signupInviteService->getExpiredInvites(),
            default => $this->signupInviteService->getAllInvites(),
        };

        return response()->json([
            'invites' => $invites->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    'token' => $invite->token,
                    'signup_url' => $invite->signup_url,
                    'is_used' => $invite->isUsed(),
                    'is_expired' => $invite->isExpired(),
                    'is_valid' => $invite->isValid(),
                    'used_at' => $invite->used_at,
                    'expires_at' => $invite->expires_at,
                    'created_at' => $invite->created_at,
                    'creator' => [
                        'id' => $invite->creator->id,
                        'name' => $invite->creator->name,
                        'email' => $invite->creator->email,
                    ],
                    'used_by' => $invite->usedByUser ? [
                        'id' => $invite->usedByUser->id,
                        'name' => $invite->usedByUser->name,
                        'email' => $invite->usedByUser->email,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Get a specific invite by token (admin only).
     */
    public function show(string $token): JsonResponse
    {
        $invite = $this->signupInviteService->getByToken($token);

        if (!$invite) {
            return response()->json([
                'message' => 'Invite not found',
            ], 404);
        }

        return response()->json([
            'invite' => [
                'id' => $invite->id,
                'token' => $invite->token,
                'signup_url' => $invite->signup_url,
                'is_used' => $invite->isUsed(),
                'is_expired' => $invite->isExpired(),
                'is_valid' => $invite->isValid(),
                'used_at' => $invite->used_at,
                'expires_at' => $invite->expires_at,
                'created_at' => $invite->created_at,
                'creator' => [
                    'id' => $invite->creator->id,
                    'name' => $invite->creator->name,
                    'email' => $invite->creator->email,
                ],
                'used_by' => $invite->usedByUser ? [
                    'id' => $invite->usedByUser->id,
                    'name' => $invite->usedByUser->name,
                    'email' => $invite->usedByUser->email,
                ] : null,
            ],
        ]);
    }

    /**
     * Delete an invite (admin only).
     */
    public function destroy(string $token): JsonResponse
    {
        $invite = $this->signupInviteService->getByToken($token);

        if (!$invite) {
            return response()->json([
                'message' => 'Invite not found',
            ], 404);
        }

        $invite->delete();

        return response()->json([
            'message' => 'Invite deleted successfully',
        ]);
    }
}
