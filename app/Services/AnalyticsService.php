<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardAnalytics(?User $user = null): array
    {
        if ($user) {
            return $this->getUserDashboardAnalytics($user);
        }

        return [
            'overview' => $this->getGlobalOverview(),
            'recent_projects' => $this->getRecentProjects(),
            'active_tasks' => $this->getActiveTasks(),
            'revenue' => $this->getRevenueStats(),
            'worker_productivity' => $this->getWorkerProductivity(),
        ];
    }

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

    public function getUserDashboardAnalytics(User $user): array
    {
        $analytics = [
            'user_info' => [
                'name' => $user->name,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ],
        ];

        if ($user->isClient()) {
            $analytics['projects'] = $this->getClientProjectsAnalytics($user);
            $analytics['tasks'] = $this->getClientTasksAnalytics($user);
        }

        if ($user->isWorker()) {
            $analytics['tasks'] = $this->getWorkerTasksAnalytics($user);
            $analytics['time_tracking'] = $this->getWorkerTimeTrackingAnalytics($user);
        }

        if ($user->isAdmin()) {
            $analytics['overview'] = $this->getGlobalOverview();
        }

        return $analytics;
    }

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

    public function getExecutiveSummary(): array
    {
        return [
            'projects' => $this->getProjectKPIs(),
            'tasks' => $this->getTaskKPIs(),
            'financial' => $this->getFinancialKPIs(),
            'team' => $this->getTeamKPIs(),
        ];
    }

    public function getProjectKPIs(): array
    {
        $total = Project::count();
        $active = Project::where('status', 'active')->count();
        $completed = Project::where('status', 'completed')->count();
        $onHold = Project::where('status', 'on_hold')->count();

        return [
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'on_hold' => $onHold,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'avg_duration_days' => $this->getAverageProjectDuration(),
        ];
    }

    public function getTaskKPIs(): array
    {
        $total = Task::count();
        $completed = Task::where('status', 'completed')->count();
        $overdue = Task::where('deadline', '<', now())
            ->whereNotIn('status', ['completed'])
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'todo' => Task::where('status', 'todo')->count(),
            'overdue' => $overdue,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    public function getFinancialKPIs(): array
    {
        $totalInvoiced = Invoice::sum('amount');
        $paidInvoices = Invoice::where('status', 'paid')->sum('amount');
        $pendingInvoices = Invoice::where('status', 'pending')->sum('amount');
        $overdueInvoices = Invoice::where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('amount');

        $totalTransactions = Transaction::where('status', 'completed')->sum('amount');

        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $paidInvoices,
            'total_pending' => $pendingInvoices,
            'total_overdue' => $overdueInvoices,
            'payment_rate' => $totalInvoiced > 0 ? round(($paidInvoices / $totalInvoiced) * 100, 2) : 0,
            'total_transactions' => $totalTransactions,
        ];
    }

    public function getTeamKPIs(): array
    {
        $totalWorkers = User::where('role', 'worker')->count();
        $activeWorkers = User::where('role', 'worker')
            ->whereHas('timeTracking', function ($query) {
                $query->where('started_at', '>=', now()->subHours(24));
            })
            ->count();

        return [
            'total_workers' => $totalWorkers,
            'active_today' => $activeWorkers,
            'total_clients' => User::where('role', 'client')->count(),
        ];
    }

    private function getAverageProjectDuration(): int
    {
        $completedProjects = Project::where('status', 'completed')
            ->whereNotNull('end_date')
            ->whereNotNull('start_date')
            ->get();

        if ($completedProjects->isEmpty()) {
            return 0;
        }

        $totalDays = $completedProjects->sum(function ($project) {
            return $project->start_date->diffInDays($project->end_date);
        });

        return round($totalDays / $completedProjects->count());
    }

    public function getTimeSeriesData(string $metric, string $period = '30days'): array
    {
        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '1year' => now()->subYear(),
            default => now()->subDays(30),
        };

        return match($metric) {
            'revenue' => $this->getRevenueTimeSeries($startDate),
            'tasks' => $this->getTaskTimeSeries($startDate),
            'projects' => $this->getProjectTimeSeries($startDate),
            default => [],
        };
    }

    private function getRevenueTimeSeries(Carbon $startDate): array
    {
        return Transaction::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getTaskTimeSeries(Carbon $startDate): array
    {
        return Task::where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getProjectTimeSeries(Carbon $startDate): array
    {
        return Project::where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->pluck('total', 'date')
            ->toArray();
    }

    public function generateReport(string $type, array $filters = []): array
    {
        return match($type) {
            'project_summary' => $this->generateProjectSummaryReport($filters),
            'task_summary' => $this->generateTaskSummaryReport($filters),
            'financial_summary' => $this->generateFinancialSummaryReport($filters),
            'team_performance' => $this->generateTeamPerformanceReport($filters),
            'client_summary' => $this->generateClientSummaryReport($filters),
            default => [],
        };
    }

    private function generateProjectSummaryReport(array $filters): array
    {
        $query = Project::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $projects = $query->with(['client', 'tasks', 'workers'])->get();

        return [
            'total_projects' => $projects->count(),
            'by_status' => $projects->groupBy('status')->map->count(),
            'by_client' => $projects->groupBy('client_id')->map->count(),
            'total_budget' => $projects->sum('budget'),
            'avg_progress' => $projects->avg('progress_percentage'),
            'projects' => $projects->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client' => $p->client->name ?? null,
                'status' => $p->status,
                'budget' => $p->budget,
                'progress' => $p->progress_percentage,
                'task_count' => $p->tasks->count(),
            ]),
        ];
    }

    private function generateTaskSummaryReport(array $filters): array
    {
        $query = Task::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        $tasks = $query->with(['project', 'assignedWorker'])->get();

        return [
            'total_tasks' => $tasks->count(),
            'by_status' => $tasks->groupBy('status')->map->count(),
            'by_priority' => $tasks->groupBy('priority')->map->count(),
            'overdue_count' => $tasks->where('deadline', '<', now())->whereNotIn('status', ['completed'])->count(),
            'completion_rate' => $tasks->count() > 0 
                ? round(($tasks->where('status', 'completed')->count() / $tasks->count()) * 100, 2) 
                : 0,
        ];
    }

    private function generateFinancialSummaryReport(array $filters): array
    {
        $invoiceQuery = Invoice::query();
        $transactionQuery = Transaction::where('status', 'completed');

        if (!empty($filters['client_id'])) {
            $invoiceQuery->where('client_id', $filters['client_id']);
            $transactionQuery->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['date_from'])) {
            $invoiceQuery->where('created_at', '>=', $filters['date_from']);
            $transactionQuery->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $invoiceQuery->where('created_at', '<=', $filters['date_to']);
            $transactionQuery->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'invoices' => [
                'total' => $invoiceQuery->count(),
                'total_amount' => $invoiceQuery->sum('amount'),
                'paid' => $invoiceQuery->where('status', 'paid')->sum('amount'),
                'pending' => $invoiceQuery->where('status', 'pending')->sum('amount'),
            ],
            'transactions' => [
                'total' => $transactionQuery->count(),
                'total_amount' => $transactionQuery->sum('amount'),
            ],
        ];
    }

    private function generateTeamPerformanceReport(array $filters): array
    {
        $workers = User::where('role', 'worker')
            ->with(['assignedTasks', 'timeTracking'])
            ->get();

        return $workers->map(function ($worker) {
            $tasks = $worker->assignedTasks;
            $completedTasks = $tasks->where('status', 'completed')->count();
            $totalTasks = $tasks->count();
            $totalHours = $worker->timeTracking->sum('duration_seconds') / 3600;

            return [
                'id' => $worker->id,
                'name' => $worker->name,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                'total_hours' => round($totalHours, 2),
                'on_time_rate' => $this->calculateOnTimeRate($worker),
            ];
        })->toArray();
    }

    private function calculateOnTimeRate(User $worker): float
    {
        $completedTasks = $worker->assignedTasks->where('status', 'completed');
        
        if ($completedTasks->isEmpty()) {
            return 0;
        }

        $onTime = $completedTasks->filter(function ($task) {
            return !$task->completed_at || !$task->deadline || $task->completed_at->lte($task->deadline);
        })->count();

        return round(($onTime / $completedTasks->count()) * 100, 2);
    }

    private function generateClientSummaryReport(array $filters): array
    {
        $query = Client::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $clients = $query->with(['projects', 'invoices'])->get();

        return $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'project_count' => $client->projects->count(),
                'total_invoiced' => $client->invoices->sum('amount'),
                'total_paid' => $client->invoices->where('status', 'paid')->sum('amount'),
            ];
        })->toArray();
    }
}
