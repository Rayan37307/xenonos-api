<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NoteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NoteController extends Controller
{
    public function __construct(
        private NoteService $noteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->noteService->getAll($filters, $perPage);

        return response()->json([
            'notes' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function myNotes(Request $request): JsonResponse
    {
        $notes = $this->noteService->getUserNotes($request->user()->id);

        return response()->json([
            'notes' => $notes,
        ]);
    }

    public function forEntity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noteable_type' => ['required', 'string'],
            'noteable_id' => ['required', 'integer'],
        ]);

        $notes = $this->noteService->getForEntity(
            $validated['noteable_type'],
            $validated['noteable_id']
        );

        return response()->json([
            'notes' => $notes,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $note = $this->noteService->getById($id);

        return response()->json([
            'note' => $note,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noteable_type' => ['nullable', 'string'],
            'noteable_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'color' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $note = $this->noteService->create($validated);

        return response()->json([
            'message' => 'Note created successfully',
            'note' => $note,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $note = $this->noteService->getById($id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'color' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $note = $this->noteService->update($note, $validated);

        return response()->json([
            'message' => 'Note updated successfully',
            'note' => $note,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $note = $this->noteService->getById($id);
        $this->noteService->delete($note);

        return response()->json([
            'message' => 'Note deleted successfully',
        ]);
    }

    public function togglePin(int $id): JsonResponse
    {
        $note = $this->noteService->getById($id);
        $note = $this->noteService->togglePin($note);

        return response()->json([
            'message' => 'Note pin toggled successfully',
            'note' => $note,
        ]);
    }
}
