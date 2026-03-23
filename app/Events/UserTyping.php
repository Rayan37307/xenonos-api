<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $userName;
    public ?int $receiverId;
    public ?int $projectId;
    public bool $isTyping;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $userName, ?int $receiverId = null, ?int $projectId = null, bool $isTyping = true)
    {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->receiverId = $receiverId;
        $this->projectId = $projectId;
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->projectId) {
            $channels[] = new Channel('project.' . $this->projectId);
        }

        if ($this->receiverId) {
            $channels[] = new PrivateChannel('chat.' . $this->receiverId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'is_typing' => $this->isTyping,
        ];
    }
}
