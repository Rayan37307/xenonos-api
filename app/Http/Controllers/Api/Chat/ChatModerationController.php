<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Services\ChatModerationService;
use App\Models\Message;
use App\Models\UserBan;
use App\Models\MessageFlag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatModerationController extends Controller
{
    public function __construct(
        private ChatModerationService $moderationService
    ) {}

    public function deleteMessage(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $message = $this->moderationService->deleteMessage(
            $message,
            $request->user()->id,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Message deleted successfully',
            'message_id' => $message->id,
        ]);
    }

    public function restoreMessage(int $id): JsonResponse
    {
        $message = Message::findOrFail($id);
        $message = $this->moderationService->restoreMessage($message);

        return response()->json([
            'message' => 'Message restored successfully',
        ]);
    }

    public function flagMessage(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $flag = $this->moderationService->flagMessage(
            $message,
            $request->user()->id,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Message flagged successfully',
        ], 201);
    }

    public function getFlaggedMessages(Request $request): JsonResponse
    {
        $filters = $request->only(['is_resolved']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->moderationService->getFlaggedMessages($filters, $perPage);

        return response()->json([
            'flags' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function resolveFlag(Request $request, int $flagId): JsonResponse
    {
        $flag = MessageFlag::findOrFail($flagId);

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string',
        ]);

        $flag = $this->moderationService->resolveFlag(
            $flag,
            $validated['resolution_notes'] ?? null
        );

        return response()->json([
            'message' => 'Flag resolved successfully',
            'flag' => $flag,
        ]);
    }

    public function muteUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'muted_user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);

        $mute = $this->moderationService->muteUser(
            $request->user()->id,
            $validated['muted_user_id'],
            $validated['reason'] ?? null,
            $validated['expires_at'] ?? null
        );

        return response()->json([
            'message' => 'User muted successfully',
            'mute' => $mute,
        ], 201);
    }

    public function unmuteUser(Request $request, int $mutedUserId): JsonResponse
    {
        $this->moderationService->unmuteUser($request->user()->id, $mutedUserId);

        return response()->json([
            'message' => 'User unmuted successfully',
        ]);
    }

    public function getMutedUsers(Request $request): JsonResponse
    {
        $userId = $request->query('user_id', $request->user()->id);
        $muted = $this->moderationService->getMutedUsers($userId);

        return response()->json([
            'muted_users' => $muted,
        ]);
    }

    public function banUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string',
            'ban_type' => 'sometimes|in:temporary,permanent',
            'expires_at' => 'nullable|date',
            'is_permanent' => 'nullable|boolean',
        ]);

        $ban = $this->moderationService->banUser(
            $validated['user_id'],
            $request->user()->id,
            $validated
        );

        return response()->json([
            'message' => 'User banned successfully',
            'ban' => $ban,
        ], 201);
    }

    public function unbanUser(int $userId): JsonResponse
    {
        $this->moderationService->unbanUser($userId);

        return response()->json([
            'message' => 'User unbanned successfully',
        ]);
    }

    public function getBannedUsers(Request $request): JsonResponse
    {
        $filters = $request->only(['is_permanent', 'ban_type']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->moderationService->getBannedUsers($filters, $perPage);

        return response()->json([
            'banned_users' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function checkCanSendMessage(int $userId): JsonResponse
    {
        $result = $this->moderationService->checkUserCanSendMessage($userId);

        return response()->json($result);
    }

    public function getMessageAuditLogs(int $messageId): JsonResponse
    {
        $logs = $this->moderationService->getMessageAuditLogs($messageId);

        return response()->json([
            'audit_logs' => $logs,
        ]);
    }
}
