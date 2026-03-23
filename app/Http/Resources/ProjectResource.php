<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'budget' => $this->budget,
            'deadline' => $this->deadline?->toDateString(),
            'progress_percentage' => $this->progress_percentage,
            'task_count_by_status' => $this->task_count_by_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'company_name' => $this->client->company_name,
                    'user' => [
                        'id' => $this->client->user->id,
                        'name' => $this->client->user->name,
                        'email' => $this->client->user->email,
                    ],
                ];
            }),
            
            'workers' => $this->whenLoaded('workers', function () {
                return $this->workers->map(function ($worker) {
                    return [
                        'id' => $worker->id,
                        'name' => $worker->name,
                        'email' => $worker->email,
                        'avatar' => $worker->avatar ?? $worker->getFirstMediaUrl('avatar'),
                        'pivot' => [
                            'role' => $worker->pivot->role,
                        ],
                    ];
                });
            }),
            
            'tasks' => $this->whenLoaded('tasks', function () {
                return TaskResource::collection($this->tasks);
            }),
        ];
    }
}
