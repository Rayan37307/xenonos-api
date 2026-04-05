<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\UserActivityController;
use App\Http\Controllers\Api\User\AdminUserController;
use App\Http\Controllers\Api\Admin\SystemLogController;
use App\Http\Controllers\Api\Admin\SignupInviteController;
use App\Http\Controllers\Api\SignupInviteRegistrationController;
use App\Http\Controllers\Api\Project\ProjectController;
use App\Http\Controllers\Api\Task\TaskController;
use App\Http\Controllers\Api\Task\TimeTrackingController;
use App\Http\Controllers\Api\Chat\ChatController;
use App\Http\Controllers\Api\Chat\TypingIndicatorController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Analytics\DashboardController;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\File\FileController;
use App\Http\Controllers\Api\ServiceOrder\ServiceOrderController;
use App\Http\Controllers\Api\Session\SessionController;
use App\Http\Controllers\Api\Client\ClientController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\Chat\ChatModerationController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public signup invite routes (for registration with invite token)
Route::prefix('signup-invites')->group(function () {
    Route::get('{token}', [SignupInviteRegistrationController::class, 'validateToken']);
    Route::post('{token}/register', [SignupInviteRegistrationController::class, 'register']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Dashboard
    Route::get('dashboard', [LoginController::class, 'dashboardApi']);

    // Authentication
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/avatar', [AuthController::class, 'updateAvatar']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    // Session management routes
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::get('/current', [SessionController::class, 'current']);
        Route::delete('/{id}', [SessionController::class, 'destroy']);
        Route::post('/revoke-others', [SessionController::class, 'revokeOthers']);
        Route::post('/revoke-all', [SessionController::class, 'revokeAll']);
    });

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::get('activity-summary', [UserController::class, 'activitySummary']);
        Route::get('activity', [UserActivityController::class, 'index']);
    });

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('users', [AdminUserController::class, 'index']);
            Route::get('users/{id}', [AdminUserController::class, 'show']);
            Route::put('users/{id}', [AdminUserController::class, 'update']);
            Route::delete('users/{id}', [AdminUserController::class, 'destroy']);
            Route::get('workers', [AdminUserController::class, 'workers']);
            Route::get('clients', [ClientController::class, 'index']);
            Route::get('clients/{client}', [ClientController::class, 'show']);
            Route::post('clients', [ClientController::class, 'store']);
            Route::put('clients/{client}', [ClientController::class, 'update']);
            Route::delete('clients/{client}', [ClientController::class, 'destroy']);
            Route::get('clients/{client}/stats', [ClientController::class, 'stats']);

            // Signup Invite routes (admin only)
            Route::apiResource('signup-invites', SignupInviteController::class)->except(['show']);
            Route::get('signup-invites/{token}', [SignupInviteController::class, 'show']);

            // Invoice routes (admin only)
            Route::post('invoices', [InvoiceController::class, 'store']);
            Route::put('invoices/{id}', [InvoiceController::class, 'update']);
            Route::delete('invoices/{id}', [InvoiceController::class, 'destroy']);

            // Service Order routes (admin only)
            Route::get('service-orders', [ServiceOrderController::class, 'index']);
            Route::get('service-orders/{id}', [ServiceOrderController::class, 'show']);
            Route::put('service-orders/{id}', [ServiceOrderController::class, 'update']);
            Route::delete('service-orders/{id}', [ServiceOrderController::class, 'destroy']);
            Route::post('service-orders/{id}/status', [ServiceOrderController::class, 'updateStatus']);
            Route::post('service-orders/{id}/notes', [ServiceOrderController::class, 'addAdminNotes']);

            // System Log routes (admin only)
            Route::get('system-logs', [SystemLogController::class, 'index']);
            Route::get('system-logs/stats', [SystemLogController::class, 'stats']);
            Route::get('system-logs/{id}', [SystemLogController::class, 'show']);
            Route::delete('system-logs/{id}', [SystemLogController::class, 'destroy']);

            // Chat Moderation Admin routes
            Route::get('flagged-messages', [ChatModerationController::class, 'getFlaggedMessages']);
            Route::post('flags/{flagId}/resolve', [ChatModerationController::class, 'resolveFlag']);
            Route::post('ban', [ChatModerationController::class, 'banUser']);
            Route::delete('ban/{userId}', [ChatModerationController::class, 'unbanUser']);
            Route::get('banned-users', [ChatModerationController::class, 'getBannedUsers']);
            Route::get('users/{userId}/can-send-message', [ChatModerationController::class, 'checkCanSendMessage']);
        });
    });

    // Invoice routes (public to authenticated users)
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show']);

    // Service Order routes (public to authenticated users)
    Route::post('service-orders', [ServiceOrderController::class, 'store']);

    // Project routes
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/my', [ProjectController::class, 'myProjects']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::put('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);
        Route::post('/{id}/workers', [ProjectController::class, 'assignWorkers']);
        Route::delete('/{projectId}/workers/{workerId}', [ProjectController::class, 'removeWorker']);
        Route::get('/{id}/statistics', [ProjectController::class, 'statistics']);
        
        // Workspace routes
        Route::get('/{id}/workspace', [ProjectController::class, 'workspace']);
        Route::get('/{id}/details', [ProjectController::class, 'getDetails']);
        Route::put('/{id}/details', [ProjectController::class, 'updateDetails']);
        Route::get('/{id}/timeline', [ProjectController::class, 'timeline']);
        Route::get('/{id}/activity', [ProjectController::class, 'activity']);
        
        // Project events
        Route::post('/{id}/events', [ProjectController::class, 'createEvent']);
        Route::put('/events/{eventId}', [ProjectController::class, 'updateEvent']);
        Route::delete('/events/{eventId}', [ProjectController::class, 'deleteEvent']);
        
        // File linking
        Route::post('/{id}/link-file', [ProjectController::class, 'linkFile']);
        Route::delete('/{id}/files/{fileId}', [ProjectController::class, 'unlinkFile']);
    });

    // Task routes (static paths like /my must be registered before /{id})
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/my', [TaskController::class, 'myTasks']);
        Route::get('/calendar', [TaskController::class, 'calendar']);
        Route::get('/analytics', [TaskController::class, 'analytics']);
        Route::get('/upcoming', [TaskController::class, 'upcoming']);
        Route::get('/overdue', [TaskController::class, 'overdue']);
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
        Route::post('/{id}/assign', [TaskController::class, 'assignToWorker']);
        Route::post('/{id}/progress', [TaskController::class, 'updateProgress']);
        Route::post('/{id}/start-timer', [TimeTrackingController::class, 'start']);
        Route::post('/{id}/stop-timer', [TimeTrackingController::class, 'stop']);
        Route::get('/{id}/time-logs', [TimeTrackingController::class, 'logs']);
    });

    // Task Kanban routes
    Route::get('projects/{projectId}/tasks/kanban', [TaskController::class, 'kanban']);
    Route::post('projects/{projectId}/tasks/reorder', [TaskController::class, 'reorder']);

    // Time tracking routes
    Route::prefix('time-tracking')->group(function () {
        Route::get('my-logs', [TimeTrackingController::class, 'myLogs']);
    });

    // Chat routes
    Route::prefix('chat')->group(function () {
        Route::apiResource('channels', App\Http\Controllers\Api\Chat\ChannelController::class);
        Route::post('channels/{id}/members', [App\Http\Controllers\Api\Chat\ChannelController::class, 'addMember']);
        Route::delete('channels/{id}/members/{userId}', [App\Http\Controllers\Api\Chat\ChannelController::class, 'removeMember']);
        Route::get('channels/{id}/messages', [App\Http\Controllers\Api\Chat\ChannelController::class, 'messages']);
        Route::post('channels/{id}/messages', [App\Http\Controllers\Api\Chat\ChannelController::class, 'sendMessage']);

        Route::get('conversations', [App\Http\Controllers\Api\Chat\ConversationController::class, 'index']);
        Route::post('conversations', [App\Http\Controllers\Api\Chat\ConversationController::class, 'store']);
        Route::get('conversations/{id}/messages', [App\Http\Controllers\Api\Chat\ConversationController::class, 'messages']);
        Route::post('conversations/{id}/messages', [App\Http\Controllers\Api\Chat\ConversationController::class, 'sendMessage']);

        Route::put('messages/{id}', [App\Http\Controllers\Api\Chat\MessageController::class, 'update']);
        Route::delete('messages/{id}', [App\Http\Controllers\Api\Chat\MessageController::class, 'destroy']);
        Route::post('messages/{id}/reactions', [App\Http\Controllers\Api\Chat\MessageController::class, 'addReaction']);
        Route::delete('messages/{id}/reactions/{emoji}', [App\Http\Controllers\Api\Chat\MessageController::class, 'removeReaction']);
        Route::post('messages/{id}/read', [App\Http\Controllers\Api\Chat\MessageController::class, 'markAsRead']);

        // Chat Moderation routes
        Route::post('messages/{id}/flag', [ChatModerationController::class, 'flagMessage']);
        Route::post('messages/{id}/delete', [ChatModerationController::class, 'deleteMessage']);
        Route::post('messages/{id}/restore', [ChatModerationController::class, 'restoreMessage']);
        Route::get('messages/{id}/audit-logs', [ChatModerationController::class, 'getMessageAuditLogs']);
        Route::post('mute', [ChatModerationController::class, 'muteUser']);
        Route::delete('mute/{mutedUserId}', [ChatModerationController::class, 'unmuteUser']);
        Route::get('muted-users', [ChatModerationController::class, 'getMutedUsers']);

        Route::get('users/online-status', [App\Http\Controllers\Api\Chat\ChatController::class, 'onlineStatus']);
        Route::post('upload', [App\Http\Controllers\Api\Chat\ChatController::class, 'upload']);
        Route::get('search', [App\Http\Controllers\Api\Chat\ChatController::class, 'search']);
        
        Route::get('unread-count', [App\Http\Controllers\Api\Notification\NotificationController::class, 'unreadCount']); // Aliased here for convenience
        Route::post('typing', [TypingIndicatorController::class, 'store']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/send', [NotificationController::class, 'send']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    });

    // Analytics routes
    Route::prefix('analytics')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('overview', [DashboardController::class, 'overview']);
        Route::get('recent-projects', [DashboardController::class, 'recentProjects']);
        Route::get('active-tasks', [DashboardController::class, 'activeTasks']);
        Route::get('revenue-stats', [DashboardController::class, 'revenueStats']);
        Route::get('worker-productivity', [DashboardController::class, 'workerProductivity']);
    });

    // File routes
    Route::apiResource('files', FileController::class);
    Route::get('files/{id}/download', [FileController::class, 'download'])->name('files.download');

    // Announcement routes
    Route::prefix('announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index']);
        Route::get('/published', [AnnouncementController::class, 'published']);
        Route::get('/{id}', [AnnouncementController::class, 'show']);
        Route::post('/', [AnnouncementController::class, 'store']);
        Route::put('/{id}', [AnnouncementController::class, 'update']);
        Route::delete('/{id}', [AnnouncementController::class, 'destroy']);
        Route::post('/{id}/publish', [AnnouncementController::class, 'publish']);
        Route::post('/{id}/unpublish', [AnnouncementController::class, 'unpublish']);
    });

    // Note routes
    Route::prefix('notes')->group(function () {
        Route::get('/', [NoteController::class, 'index']);
        Route::get('/my', [NoteController::class, 'myNotes']);
        Route::get('/entity', [NoteController::class, 'forEntity']);
        Route::get('/{id}', [NoteController::class, 'show']);
        Route::post('/', [NoteController::class, 'store']);
        Route::put('/{id}', [NoteController::class, 'update']);
        Route::delete('/{id}', [NoteController::class, 'destroy']);
        Route::post('/{id}/toggle-pin', [NoteController::class, 'togglePin']);
    });
});
