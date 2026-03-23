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

    /**
     * Get dashboard analytics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Admin gets global analytics, others get personalized
        if ($user->isAdmin()) {
            $analytics = $this->analyticsService->getDashboardAnalytics();
        } else {
            $analytics = $this->analyticsService->getDashboardAnalytics($user);
        }

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get global overview (admin only).
     */
    public function overview(): JsonResponse
    {
        return response()->json([
            'overview' => $this->analyticsService->getGlobalOverview(),
        ]);
    }

    /**
     * Get recent projects.
     */
    public function recentProjects(): JsonResponse
    {
        $limit = request()->query('limit', 5);
        
        return response()->json([
            'projects' => $this->analyticsService->getRecentProjects($limit),
        ]);
    }

    /**
     * Get active tasks.
     */
    public function activeTasks(): JsonResponse
    {
        $limit = request()->query('limit', 10);
        
        return response()->json([
            'tasks' => $this->analyticsService->getActiveTasks($limit),
        ]);
    }

    /**
     * Get revenue statistics (admin only).
     */
    public function revenueStats(): JsonResponse
    {
        return response()->json([
            'revenue' => $this->analyticsService->getRevenueStats(),
        ]);
    }

    /**
     * Get worker productivity stats (admin only).
     */
    public function workerProductivity(): JsonResponse
    {
        return response()->json([
            'productivity' => $this->analyticsService->getWorkerProductivity(),
        ]);
    }
}
