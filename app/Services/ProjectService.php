<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDetails;
use App\Models\ProjectEvent;
use App\Models\Task;
use App\Models\File;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ProjectService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
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

        if (isset($filters['worker_id'])) {
            $query->whereHas('workers', function ($q) use ($filters) {
                $q->where('user_id', $filters['worker_id']);
            });
        }

        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSortFields = ['name', 'status', 'budget', 'deadline', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        return $query->with(['client', 'workers', 'tasks'])
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

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

    public function update(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'] ?? $project->name,
            'description' => $data['description'] ?? $project->description,
            'client_id' => $data['client_id'] ?? $project->client_id,
            'status' => $data['status'] ?? $project->status,
            'budget' => $data['budget'] ?? $project->budget,
            'deadline' => $data['deadline'] ?? $project->deadline,
        ]);

        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    public function assignWorkers(Project $project, array $workerIds): void
    {
        $attachments = [];
        foreach ($workerIds as $workerId) {
            $attachments[$workerId] = ['role' => 'worker'];
        }
        
        $project->workers()->syncWithoutDetaching($attachments);
    }

    public function removeWorker(Project $project, int $workerId): void
    {
        $project->workers()->detach($workerId);
    }

    public function getClientProjects(int $clientId, array $filters = []): Collection
    {
        $query = Project::with(['workers', 'tasks'])
            ->where('client_id', $clientId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->get();
    }

    public function getWorkerProjects(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Project::query()
            ->whereHas('workers', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['client', 'workers', 'tasks'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

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
            'budget_used' => $tasks->sum('estimated_hours') ?? 0,
            'budget_remaining' => $project->budget ? $project->budget - ($tasks->sum('estimated_hours') ?? 0) : null,
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'completed_projects' => Project::where('status', 'completed')->count(),
            'on_hold_projects' => Project::where('status', 'on_hold')->count(),
            'total_budget' => Project::sum('budget'),
        ];
    }

    public function getWorkspaceData(Project $project): array
    {
        $project->load(['client', 'workers', 'tasks', 'files', 'details', 'details.events']);
        
        $tasks = $project->tasks;
        
        return [
            'project' => $project,
            'statistics' => $this->getStatistics($project),
            'task_breakdown' => [
                'todo' => $tasks->where('status', 'todo')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'review' => $tasks->where('status', 'review')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
            ],
            'recent_files' => $project->files->take(5),
            'upcoming_events' => $project->details?->events()
                ->where('event_date', '>=', now())
                ->orderBy('event_date')
                ->take(5)
                ->get() ?? collect(),
        ];
    }

    public function getProjectDetails(Project $project): ?ProjectDetails
    {
        return $project->details ?? $project->details()->create([
            'progress' => 0,
            'priority' => 'medium',
        ]);
    }

    public function updateProjectDetails(Project $project, array $data): ProjectDetails
    {
        $details = $this->getProjectDetails($project);
        
        $details->update([
            'overview' => $data['overview'] ?? $details->overview,
            'objectives' => $data['objectives'] ?? $details->objectives,
            'priority' => $data['priority'] ?? $details->priority,
            'start_date' => $data['start_date'] ?? $details->start_date,
            'end_date' => $data['end_date'] ?? $details->end_date,
            'actual_budget' => $data['actual_budget'] ?? $details->actual_budget,
            'progress' => $data['progress'] ?? $details->progress,
            'notes' => $data['notes'] ?? $details->notes,
        ]);

        return $details->fresh();
    }

    public function createProjectEvent(Project $project, array $data): ProjectEvent
    {
        $details = $this->getProjectDetails($project);
        
        return $details->events()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'milestone',
            'event_date' => $data['event_date'],
            'end_date' => $data['end_date'] ?? null,
            'color' => $data['color'] ?? '#3b82f6',
            'is_completed' => $data['is_completed'] ?? false,
        ]);
    }

    public function updateProjectEvent(ProjectEvent $event, array $data): ProjectEvent
    {
        $event->update([
            'title' => $data['title'] ?? $event->title,
            'description' => $data['description'] ?? $event->description,
            'type' => $data['type'] ?? $event->type,
            'event_date' => $data['event_date'] ?? $event->event_date,
            'end_date' => $data['end_date'] ?? $event->end_date,
            'color' => $data['color'] ?? $event->color,
            'is_completed' => $data['is_completed'] ?? $event->is_completed,
        ]);

        return $event->fresh();
    }

    public function deleteProjectEvent(ProjectEvent $event): bool
    {
        return $event->delete();
    }

    public function getProjectTimeline(Project $project): array
    {
        $details = $project->details;
        
        if (!$details) {
            return [];
        }

        $events = $details->events()
            ->orderBy('event_date')
            ->get();

        return [
            'start_date' => $details->start_date,
            'end_date' => $details->end_date,
            'events' => $events,
            'milestones' => $events->where('type', 'milestone'),
            'deadlines' => $events->where('type', 'deadline'),
            'meetings' => $events->where('type', 'meeting'),
        ];
    }

    public function getProjectActivityFeed(Project $project, int $perPage = 20): LengthAwarePaginator
    {
        return Activity::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->orWhere(function ($query) use ($project) {
                $query->whereIn('subject_type', [Task::class, File::class, Comment::class])
                    ->whereIn('subject_id', $project->tasks->pluck('id')->toArray());
            })
            ->orWhere(function ($query) use ($project) {
                $query->whereIn('subject_type', [ProjectDetails::class, ProjectEvent::class])
                    ->where('subject_id', $project->details?->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function linkFileToProject(Project $project, int $fileId): void
    {
        $file = \App\Models\File::findOrFail($fileId);
        $file->update([
            'fileable_type' => Project::class,
            'fileable_id' => $project->id,
        ]);
    }

    public function unlinkFileFromProject(Project $project, int $fileId): void
    {
        $file = $project->files()->findOrFail($fileId);
        $file->update([
            'fileable_type' => null,
            'fileable_id' => null,
        ]);
    }
}
