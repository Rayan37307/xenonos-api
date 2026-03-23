<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Services\ProjectService;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    /**
     * List all projects.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'client_id', 'search']);
        $projects = $this->projectService->getAll($filters);

        return response()->json([
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    /**
     * Get project by ID.
     */
    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        return response()->json([
            'project' => new ProjectResource($project),
        ]);
    }

    /**
     * Create a new project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_id' => ['required', 'exists:clients,id'],
            'status' => ['sometimes', 'in:planning,active,on_hold,completed,cancelled'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date'],
        ]);

        $project = $this->projectService->create($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => new ProjectResource($project->load(['client', 'workers'])),
        ], 201);
    }

    /**
     * Update project.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:planning,active,on_hold,completed,cancelled'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date'],
        ]);

        $project = $this->projectService->update($project, $validated);

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => new ProjectResource($project),
        ]);
    }

    /**
     * Delete project.
     */
    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $this->projectService->delete($project);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    /**
     * Assign workers to project.
     */
    public function assignWorkers(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        $validated = $request->validate([
            'worker_ids' => ['required', 'array'],
            'worker_ids.*' => ['required', 'exists:users,id'],
        ]);

        $this->projectService->assignWorkers($project, $validated['worker_ids']);

        return response()->json([
            'message' => 'Workers assigned successfully',
            'project' => new ProjectResource($project->load('workers')),
        ]);
    }

    /**
     * Remove worker from project.
     */
    public function removeWorker(Request $request, int $projectId, int $workerId): JsonResponse
    {
        $project = $this->projectService->getById($projectId);
        $this->projectService->removeWorker($project, $workerId);

        return response()->json([
            'message' => 'Worker removed successfully',
        ]);
    }

    /**
     * Get project statistics.
     */
    public function statistics(int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $stats = $this->projectService->getStatistics($project);

        return response()->json([
            'statistics' => $stats,
        ]);
    }
}
