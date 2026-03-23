<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public string $type;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, string $type = 'private')
    {
        $this->message = $message->load(['sender', 'receiver']);
        $this->type = $type;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->type === 'private' && $this->message->receiver_id) {
            // Private channel for receiver
            $channels[] = new PrivateChannel('chat.' . $this->message->receiver_id);
        } elseif ($this->type === 'project' && $this->message->project_id) {
            // Project room channel
            $channels[] = new Channel('project.' . $this->message->project_id);
        }

        // Also broadcast to sender
        if ($this->message->sender_id) {
            $channels[] = new PrivateChannel('chat.' . $this->message->sender_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'project_id' => $this->message->project_id,
            'message' => $this->message->message,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
            ],
            'type' => $this->type,
        ];
    }
}
