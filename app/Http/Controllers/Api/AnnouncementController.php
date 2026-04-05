<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function __construct(
        private AnnouncementService $announcementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->announcementService->getAll($filters, $perPage);

        return response()->json([
            'announcements' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function published(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);

        $paginator = $this->announcementService->getPublished($perPage);

        return response()->json([
            'announcements' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $announcement = $this->announcementService->getById($id);

        return response()->json([
            'announcement' => $announcement,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['sometimes', 'in:info,success,warning,danger'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'datetime'],
            'expires_at' => ['nullable', 'datetime'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $announcement = $this->announcementService->create($validated);

        return response()->json([
            'message' => 'Announcement created successfully',
            'announcement' => $announcement,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $announcement = $this->announcementService->getById($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:info,success,warning,danger'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'datetime'],
            'expires_at' => ['nullable', 'datetime'],
        ]);

        $announcement = $this->announcementService->update($announcement, $validated);

        return response()->json([
            'message' => 'Announcement updated successfully',
            'announcement' => $announcement,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $announcement = $this->announcementService->getById($id);
        $this->announcementService->delete($announcement);

        return response()->json([
            'message' => 'Announcement deleted successfully',
        ]);
    }

    public function publish(int $id): JsonResponse
    {
        $announcement = $this->announcementService->getById($id);
        $announcement = $this->announcementService->publish($announcement);

        return response()->json([
            'message' => 'Announcement published successfully',
            'announcement' => $announcement,
        ]);
    }

    public function unpublish(int $id): JsonResponse
    {
        $announcement = $this->announcementService->getById($id);
        $announcement = $this->announcementService->unpublish($announcement);

        return response()->json([
            'message' => 'Announcement unpublished successfully',
            'announcement' => $announcement,
        ]);
    }
}
