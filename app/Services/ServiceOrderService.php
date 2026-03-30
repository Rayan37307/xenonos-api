<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ServiceOrderService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get all service orders.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = ServiceOrder::query()->with(['client', 'user']);

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['service_type'])) {
            $query->where('service_type', $filters['service_type']);
        }

        return $query->latest()->get();
    }

    /**
     * Get service order by ID.
     */
    public function getById(int $id): ServiceOrder
    {
        return ServiceOrder::with(['client', 'user'])->findOrFail($id);
    }

    /**
     * Create a new service order.
     */
    public function create(array $data): ServiceOrder
    {
        $serviceOrder = ServiceOrder::create($data);

        // Notify all admins about the new service order
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $this->notificationService->sendCustomNotification(
                $admin,
                'New Service Order Proposal',
                "A new service order '{$serviceOrder->title}' has been submitted by {$serviceOrder->user->name}.",
                'service_order_created',
                [
                    'service_order_id' => $serviceOrder->id,
                    'service_type' => $serviceOrder->service_type,
                    'client_name' => $serviceOrder->client->name ?? null,
                    'budget_min' => $serviceOrder->budget_min,
                    'budget_max' => $serviceOrder->budget_max,
                ],
                true // Send email
            );
        }

        return $serviceOrder;
    }

    /**
     * Update service order.
     */
    public function update(ServiceOrder $serviceOrder, array $data): ServiceOrder
    {
        $serviceOrder->update($data);

        return $serviceOrder->fresh();
    }

    /**
     * Delete service order.
     */
    public function delete(ServiceOrder $serviceOrder): void
    {
        $serviceOrder->delete();
    }

    /**
     * Update service order status.
     */
    public function updateStatus(ServiceOrder $serviceOrder, string $status): ServiceOrder
    {
        return $this->update($serviceOrder, ['status' => $status]);
    }

    /**
     * Add admin notes to service order.
     */
    public function addAdminNotes(ServiceOrder $serviceOrder, string $notes): ServiceOrder
    {
        return $this->update($serviceOrder, ['admin_notes' => $notes]);
    }
}
