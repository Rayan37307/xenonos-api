<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemLogResource;
use App\Services\SystemLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SystemLogController extends Controller
{
    public function __construct(
        private SystemLogService $systemLogService
    ) {}

    /**
     * List all system logs with optional filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'log_name',
            'event',
            'causer_type',
            'causer_id',
            'subject_type',
            'subject_id',
            'search',
            'date_from',
            'date_to',
        ]);

        $perPage = (int) $request->query('per_page', 20);

        $paginator = $this->systemLogService->paginate($filters, $perPage);

        return SystemLogResource::collection($paginator);
    }

    /**
     * Get a single system log entry.
     */
    public function show(int $id): JsonResponse
    {
        $log = $this->systemLogService->findById($id);

        if (!$log) {
            return response()->json([
                'message' => 'Log entry not found',
            ], 404);
        }

        return response()->json([
            'log' => new SystemLogResource($log),
        ]);
    }

    /**
     * Delete a system log entry.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->systemLogService->delete($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Log entry not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Log entry deleted successfully',
        ]);
    }

    /**
     * Get system log statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'stats' => $this->systemLogService->getStats(),
        ]);
    }
}
