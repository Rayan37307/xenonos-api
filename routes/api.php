<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\AdminUserController;
use App\Http\Controllers\Api\Project\ProjectController;
use App\Http\Controllers\Api\Task\TaskController;
use App\Http\Controllers\Api\Task\TimeTrackingController;
use App\Http\Controllers\Api\Chat\ChatController;
use App\Http\Controllers\Api\Chat\TypingIndicatorController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Analytics\DashboardController;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\File\FileController;
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

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Dashboard
    Route::get('dashboard', [LoginController::class, 'dashboardApi']);

    // Authentication
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/avatar', [AuthController::class, 'updateAvatar']);

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::get('activity-summary', [UserController::class, 'activitySummary']);
    });

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('users', [AdminUserController::class, 'index']);
            Route::get('users/{id}', [AdminUserController::class, 'show']);
            Route::put('users/{id}', [AdminUserController::class, 'update']);
            Route::delete('users/{id}', [AdminUserController::class, 'destroy']);
            Route::get('workers', [AdminUserController::class, 'workers']);
            Route::get('clients', [AdminUserController::class, 'clients']);

            // Invoice routes (admin only)
            Route::post('invoices', [InvoiceController::class, 'store']);
            Route::put('invoices/{id}', [InvoiceController::class, 'update']);
            Route::delete('invoices/{id}', [InvoiceController::class, 'destroy']);
        });
    });

    // Invoice routes (public to authenticated users)
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show']);

    // Project routes
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::put('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);
        Route::post('/{id}/workers', [ProjectController::class, 'assignWorkers']);
        Route::delete('/{projectId}/workers/{workerId}', [ProjectController::class, 'removeWorker']);
        Route::get('/{id}/statistics', [ProjectController::class, 'statistics']);
    });

    // Task routes
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
        Route::post('/{id}/assign', [TaskController::class, 'assignToWorker']);
        Route::post('/{id}/progress', [TaskController::class, 'updateProgress']);
        Route::post('/{id}/start-timer', [TimeTrackingController::class, 'start']);
        Route::post('/{id}/stop-timer', [TimeTrackingController::class, 'stop']);
        Route::get('/{id}/time-logs', [TimeTrackingController::class, 'logs']);
        Route::get('/my', [TaskController::class, 'myTasks']);
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
});
