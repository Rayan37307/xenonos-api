<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;
    public string $oldStatus;
    public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, string $oldStatus, string $newStatus)
    {
        $this->task = $task->load(['project', 'assignedWorker']);
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to project room
        if ($this->task->project_id) {
            $channels[] = new Channel('project.' . $this->task->project_id);
        }

        // Broadcast to assigned worker
        if ($this->task->assigned_to) {
            $channels[] = new PrivateChannel('chat.' . $this->task->assigned_to);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'task' => [
                'id' => $this->task->id,
                'title' => $this->task->title,
                'status' => $this->task->status,
                'progress' => $this->task->progress,
            ],
            'project' => [
                'id' => $this->task->project->id,
                'name' => $this->task->project->name,
            ],
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'assigned_to' => $this->task->assignedWorker?->name,
        ];
    }
}
