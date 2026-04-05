<?php

namespace App\Http\Controllers\Api\Task;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * List all tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['project_id', 'status', 'assigned_to', 'priority', 'search', 'deadline', 'sort_field', 'sort_direction']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->taskService->getAll($filters, $perPage);

        return response()->json([
            'tasks' => TaskResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get task by ID.
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        return response()->json([
            'task' => new TaskResource($task),
        ]);
    }

    /**
     * Create a new task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_id' => ['required', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', 'in:todo,in_progress,review,completed'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'deadline' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $task = $this->taskService->create($validated);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => new TaskResource($task->load(['project', 'assignedWorker'])),
        ], 201);
    }

    /**
     * Update task.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', 'in:todo,in_progress,review,completed'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'deadline' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $task = $this->taskService->update($task, $validated);

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => new TaskResource($task),
        ]);
    }

    /**
     * Delete task.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = $this->taskService->getById($id);
        $this->taskService->delete($task);

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Assign task to worker.
     */
    public function assignToWorker(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        $validated = $request->validate([
            'worker_id' => ['required', 'exists:users,id'],
        ]);

        $task = $this->taskService->assignToWorker($task, $validated['worker_id']);

        return response()->json([
            'message' => 'Task assigned successfully',
            'task' => new TaskResource($task),
        ]);
    }

    /**
     * Update task progress.
     */
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getById($id);

        $validated = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $task = $this->taskService->updateProgress($task, $validated['progress']);

        return response()->json([
            'message' => 'Progress updated successfully',
            'task' => new TaskResource($task),
        ]);
    }

    /**
     * Get tasks for Kanban board.
     */
    public function kanban(int $projectId): JsonResponse
    {
        $kanbanTasks = $this->taskService->getKanbanTasks($projectId);

        return response()->json([
            'kanban' => $kanbanTasks,
        ]);
    }

    /**
     * Get tasks assigned to current user.
     */
    public function myTasks(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page', 15);
        
        $paginator = $this->taskService->getUserTasks($request->user()->id, $status, $perPage);

        return response()->json([
            'tasks' => TaskResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Reorder tasks in Kanban.
     */
    public function reorder(Request $request, int $projectId): JsonResponse
    {
        $validated = $request->validate([
            'task_order' => ['required', 'array'],
            'task_order.*' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $this->taskService->reorderTasks($projectId, $validated['task_order']);

        return response()->json([
            'message' => 'Tasks reordered successfully',
        ]);
    }

    /**
     * Get tasks for calendar view.
     */
    public function calendar(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id');
        $userId = $request->query('user_id');
        $start = $request->query('start');
        $end = $request->query('end');

        $tasks = $this->taskService->getCalendarTasks($projectId, $userId, $start, $end);

        return response()->json([
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * Get task analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id');
        $userId = $request->query('user_id');
        $period = $request->query('period', 'all');

        $analytics = $this->taskService->getAnalytics($projectId, $userId, $period);

        return response()->json($analytics);
    }

    /**
     * Get upcoming tasks.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $days = (int) $request->query('days', 7);

        $tasks = $this->taskService->getUpcomingTasks($userId, $days);

        return response()->json([
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * Get overdue tasks.
     */
    public function overdue(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        $tasks = $this->taskService->getOverdueTasks($userId);

        return response()->json([
            'tasks' => TaskResource::collection($tasks),
        ]);
    }
}
