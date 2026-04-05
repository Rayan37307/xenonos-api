<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Message extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'project_id',
        'channel_id',
        'conversation_id',
        'message',
        'content_hash',
        'is_read',
        'read_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (Message $message) {
            if ($message->isDirty('message') || ! $message->exists) {
                $message->content_hash = self::hashContent((string) $message->message);
            }
        });
    }

    /**
     * SHA-256 hex digest of UTF-8 message body (integrity / dedupe reference).
     */
    public static function hashContent(string $plainText): string
    {
        return hash('sha256', $plainText);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message (for legacy DMs).
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the project this message belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the channel this message belongs to.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the reactions for this message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Scope a query to only include project messages.
     */
    public function scopeProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to only include private messages between two users (legacy).
     */
    public function scopePrivate($query, int $userId1, int $userId2)
    {
        return $query->where(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId1)
                ->where('receiver_id', $userId2);
        })->orWhere(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId2)
                ->where('receiver_id', $userId1);
        })->whereNull('project_id');
    }

    /**
     * Scope a query to only include unread messages for a user (legacy).
     */
    public function scopeUnreadFor($query, int $userId)
    {
        return $query->where('receiver_id', $userId)
            ->where('is_read', false);
    }
}
