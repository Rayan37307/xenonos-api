<?php

namespace App\Http\Controllers\Api\Task;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskTimeTracking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TimeTrackingController extends Controller
{
    /**
     * Start tracking time for a task.
     */
    public function start(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $user = $request->user();

        // Check if user already has an active tracking session
        $activeSession = TaskTimeTracking::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if ($activeSession) {
            return response()->json([
                'message' => 'Time tracking already active for this task',
                'tracking' => $activeSession,
            ], 400);
        }

        $tracking = TaskTimeTracking::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Time tracking started',
            'tracking' => $tracking,
        ], 201);
    }

    /**
     * Stop tracking time for a task.
     */
    public function stop(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $user = $request->user();

        $tracking = TaskTimeTracking::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if (!$tracking) {
            return response()->json([
                'message' => 'No active time tracking session found',
            ], 400);
        }

        $tracking->update([
            'ended_at' => now(),
            'duration_seconds' => now()->diffInSeconds($tracking->started_at),
        ]);

        return response()->json([
            'message' => 'Time tracking stopped',
            'tracking' => $tracking,
        ]);
    }

    /**
     * Get time tracking logs for a task.
     */
    public function logs(int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);

        $logs = $task->timeTracking()
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                    ],
                    'started_at' => $log->started_at->toIso8601String(),
                    'ended_at' => $log->ended_at?->toIso8601String(),
                    'duration_seconds' => $log->duration_seconds,
                    'formatted_duration' => $log->formatted_duration,
                    'is_active' => $log->is_active,
                ];
            });

        $totalSeconds = $logs->sum('duration_seconds');
        $totalHours = round($totalSeconds / 3600, 2);

        return response()->json([
            'logs' => $logs,
            'total_seconds' => $totalSeconds,
            'total_hours' => $totalHours,
        ]);
    }

    /**
     * Get my time tracking logs.
     */
    public function myLogs(Request $request): JsonResponse
    {
        $user = $request->user();

        $logs = $user->timeTracking()
            ->with('task.project')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'task' => [
                        'id' => $log->task->id,
                        'title' => $log->task->title,
                        'project' => [
                            'id' => $log->task->project->id,
                            'name' => $log->task->project->name,
                        ],
                    ],
                    'started_at' => $log->started_at->toIso8601String(),
                    'ended_at' => $log->ended_at?->toIso8601String(),
                    'duration_seconds' => $log->duration_seconds,
                    'formatted_duration' => $log->formatted_duration,
                    'is_active' => $log->is_active,
                ];
            });

        // Get active session if any
        $activeSession = $user->timeTracking()
            ->whereNull('ended_at')
            ->with('task.project')
            ->first();

        return response()->json([
            'logs' => $logs,
            'active_session' => $activeSession ? [
                'id' => $activeSession->id,
                'task' => [
                    'id' => $activeSession->task->id,
                    'title' => $activeSession->task->title,
                    'project' => [
                        'id' => $activeSession->task->project->id,
                        'name' => $activeSession->task->project->name,
                    ],
                ],
                'started_at' => $activeSession->started_at->toIso8601String(),
            ] : null,
        ]);
    }
}
