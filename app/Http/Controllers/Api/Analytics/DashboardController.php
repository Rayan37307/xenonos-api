<?php

namespace App\Http\Controllers\Api\Analytics;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $analytics = $this->analyticsService->getDashboardAnalytics();
        } else {
            $analytics = $this->analyticsService->getDashboardAnalytics($user);
        }

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    public function overview(): JsonResponse
    {
        return response()->json([
            'overview' => $this->analyticsService->getGlobalOverview(),
        ]);
    }

    public function recentProjects(): JsonResponse
    {
        $limit = request()->query('limit', 5);
        
        return response()->json([
            'projects' => $this->analyticsService->getRecentProjects($limit),
        ]);
    }

    public function activeTasks(): JsonResponse
    {
        $limit = request()->query('limit', 10);
        
        return response()->json([
            'tasks' => $this->analyticsService->getActiveTasks($limit),
        ]);
    }

    public function revenueStats(): JsonResponse
    {
        return response()->json([
            'revenue' => $this->analyticsService->getRevenueStats(),
        ]);
    }

    public function workerProductivity(): JsonResponse
    {
        return response()->json([
            'productivity' => $this->analyticsService->getWorkerProductivity(),
        ]);
    }

    public function executiveSummary(): JsonResponse
    {
        return response()->json([
            'summary' => $this->analyticsService->getExecutiveSummary(),
        ]);
    }

    public function projectSummary(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'client_id', 'date_from', 'date_to']);
        
        return response()->json(
            $this->analyticsService->generateReport('project_summary', $filters)
        );
    }

    public function taskSummary(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'priority', 'assigned_to']);
        
        return response()->json(
            $this->analyticsService->generateReport('task_summary', $filters)
        );
    }

    public function financialSummary(Request $request): JsonResponse
    {
        $filters = $request->only(['client_id', 'date_from', 'date_to']);
        
        return response()->json(
            $this->analyticsService->generateReport('financial_summary', $filters)
        );
    }

    public function teamPerformance(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        
        return response()->json([
            'team' => $this->analyticsService->generateReport('team_performance', $filters),
        ]);
    }

    public function clientSummary(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        
        return response()->json([
            'clients' => $this->analyticsService->generateReport('client_summary', $filters),
        ]);
    }

    public function timeSeries(Request $request): JsonResponse
    {
        $metric = $request->query('metric', 'revenue');
        $period = $request->query('period', '30days');

        return response()->json([
            'data' => $this->analyticsService->getTimeSeriesData($metric, $period),
            'metric' => $metric,
            'period' => $period,
        ]);
    }
}
