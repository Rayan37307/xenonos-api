<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageRead;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    /**
     * Send a private message.
     */
    public function sendPrivateMessage(User $sender, int $receiverId, string $messageText): Message
    {
        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'message' => $messageText,
            'is_read' => false,
        ]);

        // Broadcast the message
        event(new MessageSent($message, 'private'));

        return $message->load(['sender', 'receiver']);
    }

    /**
     * Send a message to project chat.
     */
    public function sendProjectMessage(User $sender, int $projectId, string $messageText): Message
    {
        $message = Message::create([
            'sender_id' => $sender->id,
            'project_id' => $projectId,
            'message' => $messageText,
            'is_read' => false,
        ]);

        // Broadcast to project room
        event(new MessageSent($message, 'project'));

        return $message->load(['sender', 'project']);
    }

    /**
     * Get private conversation messages.
     */
    public function getPrivateMessages(User $user1, User $user2, int $limit = 50): Collection
    {
        return Message::private($user1->id, $user2->id)
            ->with(['sender', 'receiver'])
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Get project chat messages.
     */
    public function getProjectMessages(int $projectId, int $limit = 50): Collection
    {
        return Message::project($projectId)
            ->with(['sender', 'project'])
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Message $message): Message
    {
        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        event(new MessageRead($message));

        return $message;
    }

    /**
     * Mark all messages as read for a user in a conversation.
     */
    public function markAllAsRead(User $user, ?int $senderId = null, ?int $projectId = null): int
    {
        $query = Message::where('receiver_id', $user->id)
            ->where('is_read', false);

        if ($senderId) {
            $query->where('sender_id', $senderId);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Message::unreadFor($user->id)->count();
    }

    /**
     * Get conversations list for a user.
     */
    public function getConversations(User $user): Collection
    {
        // Get all unique users who have messaged this user
        $sentMessages = Message::where('sender_id', $user->id)
            ->whereNull('project_id')
            ->with('receiver')
            ->get();

        $receivedMessages = Message::where('receiver_id', $user->id)
            ->whereNull('project_id')
            ->with('sender')
            ->get();

        $conversations = [];

        // Process sent messages
        foreach ($sentMessages as $message) {
            $otherUserId = $message->receiver_id;
            if (!isset($conversations[$otherUserId])) {
                $conversations[$otherUserId] = [
                    'user' => $message->receiver,
                    'last_message' => $message,
                    'unread_count' => 0,
                ];
            }
        }

        // Process received messages
        foreach ($receivedMessages as $message) {
            $otherUserId = $message->sender_id;
            if (!isset($conversations[$otherUserId])) {
                $conversations[$otherUserId] = [
                    'user' => $message->sender,
                    'last_message' => $message,
                    'unread_count' => 0,
                ];
            }
            
            // Update last message if this one is newer
            if ($message->created_at > $conversations[$otherUserId]['last_message']->created_at) {
                $conversations[$otherUserId]['last_message'] = $message;
            }
            
            // Count unread
            if (!$message->is_read) {
                $conversations[$otherUserId]['unread_count']++;
            }
        }

        return collect(array_values($conversations))->sortByDesc(function ($conv) {
            return $conv['last_message']->created_at;
        })->values();
    }
}
