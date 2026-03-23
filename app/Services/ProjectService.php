<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    /**
     * Get all projects with optional filtering.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Project::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['client', 'workers', 'tasks'])->latest()->get();
    }

    /**
     * Get project by ID.
     */
    public function getById(int $id): Project
    {
        return Project::with([
            'client.user',
            'workers',
            'tasks.assignedWorker',
            'tasks.files',
            'tasks.comments.user',
        ])->findOrFail($id);
    }

    /**
     * Create a new project.
     */
    public function create(array $data): Project
    {
        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'client_id' => $data['client_id'],
            'status' => $data['status'] ?? 'planning',
            'budget' => $data['budget'] ?? null,
            'deadline' => $data['deadline'] ?? null,
        ]);

        return $project;
    }

    /**
     * Update project.
     */
    public function update(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'] ?? $project->name,
            'description' => $data['description'] ?? $project->description,
            'status' => $data['status'] ?? $project->status,
            'budget' => $data['budget'] ?? $project->budget,
            'deadline' => $data['deadline'] ?? $project->deadline,
        ]);

        return $project->fresh();
    }

    /**
     * Delete project.
     */
    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    /**
     * Assign workers to project.
     */
    public function assignWorkers(Project $project, array $workerIds): void
    {
        // Sync workers (attach new, detach removed, update existing)
        $attachments = [];
        foreach ($workerIds as $workerId) {
            $attachments[$workerId] = ['role' => 'worker'];
        }
        
        $project->workers()->sync($attachments);
    }

    /**
     * Remove worker from project.
     */
    public function removeWorker(Project $project, int $workerId): void
    {
        $project->workers()->detach($workerId);
    }

    /**
     * Get projects for a specific client.
     */
    public function getClientProjects(int $clientId): Collection
    {
        return Project::with(['workers', 'tasks'])
            ->where('client_id', $clientId)
            ->latest()
            ->get();
    }

    /**
     * Get projects where user is assigned as worker.
     */
    public function getWorkerProjects(int $userId): Collection
    {
        return Project::with(['client', 'tasks'])
            ->whereHas('workers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest()
            ->get();
    }

    /**
     * Get project statistics.
     */
    public function getStatistics(Project $project): array
    {
        $tasks = $project->tasks;
        
        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'in_progress_tasks' => $tasks->whereIn('status', ['todo', 'in_progress', 'review'])->count(),
            'progress_percentage' => $tasks->isNotEmpty() ? round($tasks->avg('progress'), 2) : 0,
            'total_workers' => $project->workers()->count(),
            'total_hours_tracked' => $tasks->sum('total_tracked_time') / 3600,
            'days_until_deadline' => $project->deadline ? now()->diffInDays($project->deadline, false) : null,
        ];
    }
}
