<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->resource instanceof Role ? $this->resource : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status ?? 'active',
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Permission list
            'permissions' => $this->when($role !== null, function () use ($role) {
                return $role->permissions->pluck('name')->values();
            }),

            // Users with this role
            'users' => $this->when($role !== null, function () use ($role) {
                return $role->users()->get()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar ?? ($user->hasMedia('avatar') ? $user->getFirstMediaUrl('avatar') : null),
                        'role' => $user->role,
                    ];
                });
            }),

            // User count
            'user_count' => $this->when($role !== null, function () use ($role) {
                return $role->users()->count();
            }),
        ];
    }
}
