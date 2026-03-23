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
        $filters = $request->only(['project_id', 'status', 'assigned_to', 'priority']);
        $tasks = $this->taskService->getAll($filters);

        return response()->json([
            'tasks' => TaskResource::collection($tasks),
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
        $tasks = $this->taskService->getUserTasks($request->user()->id, $status);

        return response()->json([
            'tasks' => TaskResource::collection($tasks),
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
}
