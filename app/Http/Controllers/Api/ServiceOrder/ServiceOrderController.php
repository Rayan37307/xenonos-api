<?php

namespace App\Http\Controllers\Api\ServiceOrder;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Http\Resources\ServiceOrderResource;
use App\Services\ServiceOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceOrderController extends Controller
{
    public function __construct(
        private ServiceOrderService $serviceOrderService
    ) {}

    /**
     * Get all service orders.
     * Admin only - requires role:admin middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['client_id', 'user_id', 'status', 'service_type']);
        $serviceOrders = $this->serviceOrderService->getAll($filters);

        return response()->json([
            'service_orders' => ServiceOrderResource::collection($serviceOrders),
        ]);
    }

    /**
     * Get a specific service order by ID.
     * Admin only - requires role:admin middleware.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'service_order' => new ServiceOrderResource(
                $this->serviceOrderService->getById($id)
            ),
        ]);
    }

    /**
     * Create a new service order.
     * Available to authenticated users.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_type' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'deadline' => ['nullable', 'date'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['client_id'] = $request->user()->clientProfile?->id;
        $validated['status'] = 'pending';

        $serviceOrder = $this->serviceOrderService->create($validated);

        return response()->json([
            'message' => 'Service order submitted successfully. An admin will review your proposal.',
            'service_order' => new ServiceOrderResource($serviceOrder->load(['client', 'user'])),
        ], 201);
    }

    /**
     * Update service order status (Admin only).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewing,approved,rejected'],
        ]);

        $serviceOrder = $this->serviceOrderService->getById($id);
        $updatedOrder = $this->serviceOrderService->updateStatus($serviceOrder, $validated['status']);

        return response()->json([
            'message' => 'Service order status updated successfully',
            'service_order' => new ServiceOrderResource($updatedOrder),
        ]);
    }

    /**
     * Add admin notes to service order (Admin only).
     */
    public function addAdminNotes(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string'],
        ]);

        $serviceOrder = $this->serviceOrderService->getById($id);
        $updatedOrder = $this->serviceOrderService->addAdminNotes($serviceOrder, $validated['admin_notes']);

        return response()->json([
            'message' => 'Admin notes added successfully',
            'service_order' => new ServiceOrderResource($updatedOrder),
        ]);
    }

    /**
     * Update service order (Admin only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $serviceOrder = $this->serviceOrderService->getById($id);

        $validated = $request->validate([
            'service_type' => ['sometimes', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:pending,reviewing,approved,rejected'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $updatedOrder = $this->serviceOrderService->update($serviceOrder, $validated);

        return response()->json([
            'message' => 'Service order updated successfully',
            'service_order' => new ServiceOrderResource($updatedOrder),
        ]);
    }

    /**
     * Delete service order (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $serviceOrder = $this->serviceOrderService->getById($id);
        $this->serviceOrderService->delete($serviceOrder);

        return response()->json([
            'message' => 'Service order deleted successfully',
        ]);
    }
}
