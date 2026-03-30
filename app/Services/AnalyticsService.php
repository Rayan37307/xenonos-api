<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;

class AnalyticsService
{
    /**
     * Get dashboard analytics.
     */
    public function getDashboardAnalytics(?User $user = null): array
    {
        // If user is provided, get personalized analytics
        if ($user) {
            return $this->getUserDashboardAnalytics($user);
        }

        // Global analytics (admin only)
        return [
            'overview' => $this->getGlobalOverview(),
            'recent_projects' => $this->getRecentProjects(),
            'active_tasks' => $this->getActiveTasks(),
            'revenue' => $this->getRevenueStats(),
            'worker_productivity' => $this->getWorkerProductivity(),
        ];
    }

    /**
     * Get global overview statistics.
     */
    public function getGlobalOverview(): array
    {
        return [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'completed_projects' => Project::where('status', 'completed')->count(),
            'total_clients' => User::where('role', 'client')->count(),
            'total_workers' => User::where('role', 'worker')->count(),
            'total_tasks' => Task::count(),
            'pending_tasks' => Task::whereIn('status', ['todo', 'in_progress'])->count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
        ];
    }

    /**
     * Get personalized dashboard analytics for a user.
     */
    public function getUserDashboardAnalytics(User $user): array
    {
        $analytics = [
            'user_info' => [
                'name' => $user->name,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ],
        ];

        // Client-specific analytics
        if ($user->isClient()) {
            $analytics['projects'] = $this->getClientProjectsAnalytics($user);
            $analytics['tasks'] = $this->getClientTasksAnalytics($user);
        }

        // Worker-specific analytics
        if ($user->isWorker()) {
            $analytics['tasks'] = $this->getWorkerTasksAnalytics($user);
            $analytics['time_tracking'] = $this->getWorkerTimeTrackingAnalytics($user);
        }

        // Admin-specific analytics
        if ($user->isAdmin()) {
            $analytics['overview'] = $this->getGlobalOverview();
        }

        return $analytics;
    }

    /**
     * Get projects analytics for a client.
     */
    private function getClientProjectsAnalytics(User $user): array
    {
        $client = $user->clientProfile;
        if (!$client) {
            return [];
        }

        $projects = $client->projects;

        return [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'active')->count(),
            'completed_projects' => $projects->where('status', 'completed')->count(),
            'projects' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'progress' => $project->progress_percentage,
                    'deadline' => $project->deadline,
                    'task_count' => $project->tasks->count(),
                ];
            }),
        ];
    }

    /**
     * Get tasks analytics for a client.
     */
    private function getClientTasksAnalytics(User $user): array
    {
        $client = $user->clientProfile;
        if (!$client) {
            return [];
        }

        $projectIds = $client->projects->pluck('id');
        $tasks = Task::whereIn('project_id', $projectIds)->get();

        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'pending_tasks' => $tasks->whereIn('status', ['todo', 'in_progress', 'review'])->count(),
            'by_status' => [
                'todo' => $tasks->where('status', 'todo')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'review' => $tasks->where('status', 'review')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
            ],
        ];
    }

    /**
     * Get tasks analytics for a worker.
     */
    private function getWorkerTasksAnalytics(User $user): array
    {
        $tasks = $user->assignedTasks;

        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'pending_tasks' => $tasks->whereIn('status', ['todo', 'in_progress', 'review'])->count(),
            'overdue_tasks' => $tasks->where('deadline', '<', now())->whereNotIn('status', ['completed'])->count(),
            'by_status' => [
                'todo' => $tasks->where('status', 'todo')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'review' => $tasks->where('status', 'review')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
            ],
            'by_priority' => [
                'urgent' => $tasks->where('priority', 'urgent')->count(),
                'high' => $tasks->where('priority', 'high')->count(),
                'medium' => $tasks->where('priority', 'medium')->count(),
                'low' => $tasks->where('priority', 'low')->count(),
            ],
        ];
    }

    /**
     * Get time tracking analytics for a worker.
     */
    private function getWorkerTimeTrackingAnalytics(User $user): array
    {
        $timeEntries = $user->timeTracking;
        $totalSeconds = $timeEntries->sum('duration_seconds');
        $todaySeconds = $timeEntries->where('started_at', '>=', now()->startOfDay())->sum('duration_seconds');

        return [
            'total_hours' => round($totalSeconds / 3600, 2),
            'today_hours' => round($todaySeconds / 3600, 2),
            'this_week_hours' => round(
                $timeEntries->where('started_at', '>=', now()->startOfWeek())->sum('duration_seconds') / 3600, 2
            ),
            'this_month_hours' => round(
                $timeEntries->where('started_at', '>=', now()->startOfMonth())->sum('duration_seconds') / 3600, 2
            ),
        ];
    }

    /**
     * Get recent projects.
     */
    public function getRecentProjects(int $limit = 5): array
    {
        return Project::with(['client', 'tasks'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client' => $project->client->user->name,
                    'status' => $project->status,
                    'progress' => $project->progress_percentage,
                    'task_count' => $project->tasks->count(),
                ];
            })
            ->toArray();
    }

    /**
     * Get active tasks summary.
     */
    public function getActiveTasks(int $limit = 10): array
    {
        return Task::whereIn('status', ['in_progress', 'review'])
            ->with(['assignedWorker', 'project'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'progress' => $task->progress,
                    'assigned_to' => $task->assignedWorker?->name,
                    'project' => $task->project->name,
                    'deadline' => $task->deadline,
                ];
            })
            ->toArray();
    }

    /**
     * Get revenue statistics.
     */
    public function getRevenueStats(): array
    {
        $totalBudget = Project::sum('budget');
        $completedBudget = Project::where('status', 'completed')->sum('budget');

        return [
            'total_budget' => $totalBudget ?? 0,
            'completed_budget' => $completedBudget ?? 0,
            'pending_budget' => ($totalBudget ?? 0) - ($completedBudget ?? 0),
            'completion_rate' => $totalBudget > 0 ? round(($completedBudget / $totalBudget) * 100, 2) : 0,
        ];
    }

    /**
     * Get worker productivity stats.
     */
    public function getWorkerProductivity(): array
    {
        return User::where('role', 'worker')
            ->with(['assignedTasks', 'timeTracking'])
            ->get()
            ->map(function ($worker) {
                $completedTasks = $worker->assignedTasks->where('status', 'completed')->count();
                $totalTasks = $worker->assignedTasks->count();
                $totalHours = $worker->timeTracking->sum('duration_seconds') / 3600;

                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'avatar' => $worker->avatar,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                    'total_hours' => round($totalHours, 2),
                    'avg_hours_per_task' => $totalTasks > 0 ? round($totalHours / $totalTasks, 2) : 0,
                ];
            })
            ->sortByDesc('completion_rate')
            ->values()
            ->toArray();
    }
}
