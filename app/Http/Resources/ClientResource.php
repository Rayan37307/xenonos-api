<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar' => $this->user->getFirstMediaUrl('avatar'),
                ];
            }),

            'projects_count' => $this->when(isset($this->projects_count), $this->projects_count),
            'invoices_count' => $this->when(isset($this->invoices_count), $this->invoices_count),
            'service_orders_count' => $this->when(isset($this->service_orders_count), $this->service_orders_count),

            'stats' => $this->when($this->relationLoaded('projects') || $this->relationLoaded('invoices'), function () {
                return $this->stats;
            }),

            'projects' => $this->whenLoaded('projects', function () {
                return ProjectResource::collection($this->projects);
            }),

            'invoices' => $this->whenLoaded('invoices', function () {
                return InvoiceResource::collection($this->invoices);
            }),

            'service_orders' => $this->whenLoaded('serviceOrders', function () {
                return ServiceOrderResource::collection($this->serviceOrders);
            }),
        ];
    }
}
