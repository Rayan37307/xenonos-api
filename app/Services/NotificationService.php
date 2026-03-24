<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\Message;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\NewMessageNotification;
use App\Notifications\TaskStatusUpdatedNotification;
use App\Notifications\CustomNotification;

class NotificationService
{
    /**
     * Send custom notification to user(s).
     */
    public function sendCustomNotification(
        User|array $users,
        string $title,
        string $message,
        string $type = 'custom',
        ?array $data = null,
        bool $sendEmail = false
    ): void {
        $users = is_array($users) ? $users : [$users];
        $notification = new CustomNotification($title, $message, $type, $data, $sendEmail);

        foreach ($users as $user) {
            if ($user) {
                $user->notify($notification);
            }
        }
    }
    /**
     * Send task assigned notification.
     */
    public function sendTaskAssigned(Task $task, User $assignee): void
    {
        $assignee->notify(new TaskAssignedNotification($task));
    }

    /**
     * Send new message notification.
     */
    public function sendNewMessage(Message $message): void
    {
        if ($message->receiver_id) {
            $receiver = User::find($message->receiver_id);
            if ($receiver) {
                $receiver->notify(new NewMessageNotification($message));
            }
        }
    }

    /**
     * Send task status updated notification.
     */
    public function sendTaskStatusUpdated(Task $task, string $oldStatus, string $newStatus): void
    {
        // Notify the task creator and project members
        $notifiables = collect();
        
        // Add task creator
        if ($task->created_by !== $task->assigned_to) {
            $notifiables->push(User::find($task->created_by));
        }
        
        // Add project workers
        $notifiables = $notifiables->merge($task->project->workers);
        
        // Remove duplicates and null values
        $notifiables = $notifiables->filter()->unique('id');

        foreach ($notifiables as $user) {
            $user->notify(new TaskStatusUpdatedNotification($task, $oldStatus, $newStatus));
        }
    }

    /**
     * Send notification to all project members.
     */
    public function notifyProjectMembers($project, $notification): void
    {
        // Get client
        $client = $project->client->user;
        if ($client) {
            $client->notify($notification);
        }

        // Get workers
        foreach ($project->workers as $worker) {
            $worker->notify($notification);
        }
    }

    /**
     * Get user notifications.
     */
    public function getUserNotifications(User $user, bool $includeRead = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = $user->notifications();
        
        if (!$includeRead) {
            $query->whereNull('read_at');
        }

        return $query->latest()->get();
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationAsRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Get unread notification count.
     */
    public function getUnreadNotificationCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
