<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;
    public User $assignee;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, User $assignee)
    {
        $this->task = $task->load(['project', 'creator']);
        $this->assignee = $assignee;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->assignee->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task.assigned';
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
                'priority' => $this->task->priority,
                'progress' => $this->task->progress,
                'deadline' => $this->task->deadline?->toDateString(),
            ],
            'project' => [
                'id' => $this->task->project->id,
                'name' => $this->task->project->name,
            ],
            'assigned_by' => [
                'id' => $this->task->creator->id,
                'name' => $this->task->creator->name,
            ],
        ];
    }
}
