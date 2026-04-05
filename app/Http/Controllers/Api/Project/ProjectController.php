<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Services\ProjectService;
use App\Http\Resources\ProjectResource;
use App\Models\ProjectEvent;
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
        $filters = $request->only(['status', 'client_id', 'search', 'worker_id', 'sort_field', 'sort_direction']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->projectService->getAll($filters, $perPage);

        return response()->json([
            'projects' => ProjectResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get projects assigned to the current user.
     */
    public function myProjects(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $filters = $request->only(['status', 'search', 'per_page']);

        $paginator = $this->projectService->getWorkerProjects($user->id, $filters);

        return response()->json([
            'projects' => ProjectResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
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

    /**
     * Get project workspace data (details, events, files, activity).
     */
    public function workspace(int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $workspace = $this->projectService->getWorkspaceData($project);

        return response()->json($workspace);
    }

    /**
     * Get or create project details.
     */
    public function getDetails(int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $details = $this->projectService->getProjectDetails($project);

        return response()->json([
            'details' => $details,
        ]);
    }

    /**
     * Update project details.
     */
    public function updateDetails(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        $validated = $request->validate([
            'overview' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'actual_budget' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $details = $this->projectService->updateProjectDetails($project, $validated);

        return response()->json([
            'message' => 'Project details updated successfully',
            'details' => $details,
        ]);
    }

    /**
     * Create project event.
     */
    public function createEvent(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:milestone,deadline,meeting,other'],
            'event_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        $event = $this->projectService->createProjectEvent($project, $validated);

        return response()->json([
            'message' => 'Project event created successfully',
            'event' => $event,
        ], 201);
    }

    /**
     * Update project event.
     */
    public function updateEvent(Request $request, int $eventId): JsonResponse
    {
        $event = ProjectEvent::findOrFail($eventId);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:milestone,deadline,meeting,other'],
            'event_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        $event = $this->projectService->updateProjectEvent($event, $validated);

        return response()->json([
            'message' => 'Project event updated successfully',
            'event' => $event,
        ]);
    }

    /**
     * Delete project event.
     */
    public function deleteEvent(int $eventId): JsonResponse
    {
        $event = ProjectEvent::findOrFail($eventId);
        $this->projectService->deleteProjectEvent($event);

        return response()->json([
            'message' => 'Project event deleted successfully',
        ]);
    }

    /**
     * Get project timeline (events).
     */
    public function timeline(int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $timeline = $this->projectService->getProjectTimeline($project);

        return response()->json([
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get project activity feed.
     */
    public function activity(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $perPage = (int) $request->query('per_page', 20);

        $activities = $this->projectService->getProjectActivityFeed($project, $perPage);

        return response()->json([
            'activities' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Link file to project.
     */
    public function linkFile(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        $validated = $request->validate([
            'file_id' => ['required', 'exists:files,id'],
        ]);

        $this->projectService->linkFileToProject($project, $validated['file_id']);

        return response()->json([
            'message' => 'File linked to project successfully',
        ]);
    }

    /**
     * Unlink file from project.
     */
    public function unlinkFile(int $id, int $fileId): JsonResponse
    {
        $project = $this->projectService->getById($id);
        $this->projectService->unlinkFileFromProject($project, $fileId);

        return response()->json([
            'message' => 'File unlinked from project successfully',
        ]);
    }
}
