<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Events\TaskAssigned;
use App\Events\TaskStatusUpdated;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * Get all tasks with optional filtering.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Task::query();

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->with(['project', 'assignedWorker', 'creator', 'files'])->latest()->get();
    }

    /**
     * Get task by ID.
     */
    public function getById(int $id): Task
    {
        return Task::with([
            'project.client',
            'assignedWorker',
            'creator',
            'files',
            'comments.user',
            'timeTracking.user',
        ])->findOrFail($id);
    }

    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'project_id' => $data['project_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $data['created_by'],
            'status' => $data['status'] ?? 'todo',
            'priority' => $data['priority'] ?? 'medium',
            'progress' => $data['progress'] ?? 0,
            'deadline' => $data['deadline'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
        ]);

        return $task;
    }

    /**
     * Update task.
     */
    public function update(Task $task, array $data): Task
    {
        $oldStatus = $task->status;
        
        $task->update([
            'title' => $data['title'] ?? $task->title,
            'description' => $data['description'] ?? $task->description,
            'assigned_to' => $data['assigned_to'] ?? $task->assigned_to,
            'status' => $data['status'] ?? $task->status,
            'priority' => $data['priority'] ?? $task->priority,
            'progress' => $data['progress'] ?? $task->progress,
            'deadline' => $data['deadline'] ?? $task->deadline,
            'estimated_hours' => $data['estimated_hours'] ?? $task->estimated_hours,
        ]);

        // Fire event if status changed
        if ($oldStatus !== $task->status) {
            event(new TaskStatusUpdated($task, $oldStatus, $task->status));
        }

        return $task->fresh();
    }

    /**
     * Delete task.
     */
    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Assign task to worker.
     */
    public function assignToWorker(Task $task, int $workerId): Task
    {
        $oldAssignee = $task->assigned_to;
        
        $task->update([
            'assigned_to' => $workerId,
            'status' => $task->status === 'todo' ? 'in_progress' : $task->status,
        ]);

        // Fire event if there's a new assignee
        if ($workerId && $workerId !== $oldAssignee) {
            event(new TaskAssigned($task, User::find($workerId)));
        }

        return $task->fresh();
    }

    /**
     * Update task progress.
     */
    public function updateProgress(Task $task, int $progress): Task
    {
        $progress = max(0, min(100, $progress));
        
        // Auto-update status based on progress
        $status = $task->status;
        if ($progress === 100) {
            $status = 'completed';
        } elseif ($progress > 0 && $task->status === 'todo') {
            $status = 'in_progress';
        }

        $task->update([
            'progress' => $progress,
            'status' => $status,
        ]);

        return $task->fresh();
    }

    /**
     * Get tasks for Kanban board.
     */
    public function getKanbanTasks(int $projectId): array
    {
        $tasks = Task::where('project_id', $projectId)
            ->with(['assignedWorker', 'files'])
            ->ordered()
            ->get();

        return [
            'todo' => $tasks->where('status', 'todo')->values(),
            'in_progress' => $tasks->where('status', 'in_progress')->values(),
            'review' => $tasks->where('status', 'review')->values(),
            'completed' => $tasks->where('status', 'completed')->values(),
        ];
    }

    /**
     * Get tasks assigned to a user.
     */
    public function getUserTasks(int $userId, ?string $status = null): Collection
    {
        $query = Task::where('assigned_to', $userId)
            ->with(['project', 'files']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->get();
    }

    /**
     * Reorder tasks in Kanban.
     */
    public function reorderTasks(int $projectId, array $taskOrder): void
    {
        foreach ($taskOrder as $position => $taskId) {
            Task::where('id', $taskId)->update(['position' => $position]);
        }
    }

    /**
     * Get task statistics for a project.
     */
    public function getStatistics(Project $project): array
    {
        $tasks = $project->tasks;

        return [
            'total' => $tasks->count(),
            'todo' => $tasks->where('status', 'todo')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'review' => $tasks->where('status', 'review')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $tasks->where('deadline', '<', now())->whereNotIn('status', ['completed'])->count(),
            'avg_progress' => $tasks->isNotEmpty() ? round($tasks->avg('progress'), 2) : 0,
        ];
    }
}
