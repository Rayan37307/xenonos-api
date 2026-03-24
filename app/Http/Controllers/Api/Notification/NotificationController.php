<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send custom notification to user(s).
     */
    public function send(Request $request): JsonResponse
    {
        // Only admins can send manual notifications
        if (!$request->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'message' => 'Only administrators can send manual notifications',
            ]);
        }

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|max:100',
            'data' => 'nullable|array',
            'send_email' => 'nullable|boolean',
        ]);

        // Determine recipients
        $users = [];
        if (!empty($validated['user_ids'])) {
            $users = User::whereIn('id', $validated['user_ids'])->get();
        } elseif (!empty($validated['user_id'])) {
            $users = [User::find($validated['user_id'])];
        } else {
            throw ValidationException::withMessages([
                'user_id' => 'Either user_id or user_ids must be provided',
            ]);
        }

        $this->notificationService->sendCustomNotification(
            $users,
            $validated['title'],
            $validated['message'],
            $validated['type'] ?? 'custom',
            $validated['data'] ?? null,
            $validated['send_email'] ?? false
        );

        return response()->json([
            'message' => 'Notification sent successfully',
            'recipients_count' => count($users),
        ]);
    }

    /**
     * Get user notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $includeRead = $request->query('include_read', false);
        $notifications = $this->notificationService->getUserNotifications(
            $request->user(),
            $includeRead
        );

        return response()->json([
            'notifications' => NotificationResource::collection($notifications),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $this->notificationService->markNotificationAsRead(
            $request->user(),
            $notificationId
        );

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllNotificationsAsRead($request->user());

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadNotificationCount($request->user());

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
