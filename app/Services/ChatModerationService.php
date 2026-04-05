<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Models\UserMute;
use App\Models\UserBan;
use App\Models\MessageFlag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatModerationService
{
    public function deleteMessage(Message $message, int $deletedById, ?string $reason = null): Message
    {
        $message->update([
            'is_deleted' => true,
            'delete_reason' => $reason,
            'deleted_by' => $deletedById,
            'deleted_at' => now(),
        ]);

        return $message;
    }

    public function restoreMessage(Message $message): Message
    {
        $message->update([
            'is_deleted' => false,
            'delete_reason' => null,
            'deleted_by' => null,
            'deleted_at' => null,
        ]);

        return $message;
    }

    public function flagMessage(Message $message, int $userId, ?string $reason = null): MessageFlag
    {
        return MessageFlag::firstOrCreate(
            ['message_id' => $message->id, 'user_id' => $userId],
            ['reason' => $reason, 'flagged_by_type' => User::class, 'flagged_by_id' => $userId]
        );
    }

    public function unflagMessage(MessageFlag $flag): bool
    {
        return $flag->delete();
    }

    public function resolveFlag(MessageFlag $flag, ?string $notes = null): MessageFlag
    {
        $flag->update([
            'is_resolved' => true,
            'resolution_notes' => $notes,
        ]);

        return $flag->fresh();
    }

    public function getFlaggedMessages(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MessageFlag::query()->with(['message', 'user']);

        if (isset($filters['is_resolved'])) {
            $query->where('is_resolved', $filters['is_resolved']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function muteUser(int $userId, int $mutedUserId, ?string $reason = null, ?\Carbon\Carbon $expiresAt = null): UserMute
    {
        return UserMute::updateOrCreate(
            ['user_id' => $userId, 'muted_user_id' => $mutedUserId],
            ['reason' => $reason, 'expires_at' => $expiresAt]
        );
    }

    public function unmuteUser(int $userId, int $mutedUserId): bool
    {
        return UserMute::where('user_id', $userId)
            ->where('muted_user_id', $mutedUserId)
            ->delete() > 0;
    }

    public function isMuted(int $userId, int $mutedUserId): bool
    {
        return UserMute::where('user_id', $userId)
            ->where('muted_user_id', $mutedUserId)
            ->active()
            ->exists();
    }

    public function getMutedUsers(int $userId): Collection
    {
        return UserMute::where('user_id', $userId)->active()->with('mutedUser')->get();
    }

    public function banUser(int $userId, int $bannedById, array $data = []): UserBan
    {
        return UserBan::create([
            'user_id' => $userId,
            'reason' => $data['reason'] ?? null,
            'ban_type' => $data['ban_type'] ?? 'temporary',
            'expires_at' => $data['expires_at'] ?? null,
            'is_permanent' => $data['is_permanent'] ?? false,
            'banned_by_type' => User::class,
            'banned_by_id' => $bannedById,
        ]);
    }

    public function unbanUser(int $userId): bool
    {
        return UserBan::where('user_id', $userId)->delete() > 0;
    }

    public function isBanned(int $userId): bool
    {
        return UserBan::where('user_id', $userId)->active()->exists();
    }

    public function getActiveBan(int $userId): ?UserBan
    {
        return UserBan::where('user_id', $userId)->active()->first();
    }

    public function getBannedUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = UserBan::query()->with('user');

        if (isset($filters['is_permanent'])) {
            $query->where('is_permanent', $filters['is_permanent']);
        }

        if (isset($filters['ban_type'])) {
            $query->where('ban_type', $filters['ban_type']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getMutedUsersList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = UserMute::query()->with(['user', 'mutedUser']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function checkUserCanSendMessage(int $userId): array
    {
        $ban = $this->getActiveBan($userId);
        
        if ($ban) {
            return [
                'can_send' => false,
                'reason' => $ban->is_permanent ? 'Permanently banned' : 'Temporarily banned',
                'expires_at' => $ban->expires_at,
            ];
        }

        return ['can_send' => true];
    }

    public function getMessageAuditLogs(int $messageId): Collection
    {
        $message = Message::findOrFail($messageId);
        
        return \Spatie\Activitylog\Models\Activity::where('subject_type', Message::class)
            ->where('subject_id', $messageId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
