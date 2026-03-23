<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'progress' => $this->progress,
            'deadline' => $this->deadline?->toDateString(),
            'estimated_hours' => $this->estimated_hours,
            'position' => $this->position,
            'formatted_tracked_time' => $this->formatted_tracked_time,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                ];
            }),
            
            'assigned_worker' => $this->whenLoaded('assignedWorker', function () {
                return $this->assignedWorker ? [
                    'id' => $this->assignedWorker->id,
                    'name' => $this->assignedWorker->name,
                    'email' => $this->assignedWorker->email,
                    'avatar' => $this->assignedWorker->avatar ?? $this->assignedWorker->getFirstMediaUrl('avatar'),
                ] : null;
            }),
            
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            
            'files' => $this->whenLoaded('files', function () {
                return $this->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'size' => $file->formatted_size,
                        'url' => $file->url,
                        'mime_type' => $file->mime_type,
                        'uploaded_at' => $file->created_at?->toIso8601String(),
                    ];
                });
            }),
        ];
    }
}
