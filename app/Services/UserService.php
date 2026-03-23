<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    /**
     * Get all users with optional filtering.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = User::query();

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with('clientProfile')->latest()->get();
    }

    /**
     * Get user by ID.
     */
    public function getById(int $id): User
    {
        return User::with(['clientProfile', 'assignedProjects', 'assignedTasks'])->findOrFail($id);
    }

    /**
     * Get all workers.
     */
    public function getAllWorkers(): Collection
    {
        return User::where('role', 'worker')
            ->with('assignedTasks')
            ->get();
    }

    /**
     * Get all clients.
     */
    public function getAllClients(): Collection
    {
        return User::where('role', 'client')
            ->with('clientProfile')
            ->get();
    }

    /**
     * Update user.
     */
    public function update(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
        ]);

        // Update client profile if exists
        if ($user->isClient() && $user->clientProfile) {
            $user->clientProfile->update([
                'company_name' => $data['company_name'] ?? $user->clientProfile->company_name,
                'phone' => $data['phone'] ?? $user->clientProfile->phone,
                'address' => $data['address'] ?? $user->clientProfile->address,
            ]);
        }

        return $user->fresh();
    }

    /**
     * Delete user.
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get user's activity summary.
     */
    public function getActivitySummary(User $user): array
    {
        return [
            'total_tasks' => $user->assignedTasks()->count(),
            'completed_tasks' => $user->assignedTasks()->where('status', 'completed')->count(),
            'in_progress_tasks' => $user->assignedTasks()->whereIn('status', ['todo', 'in_progress'])->count(),
            'total_projects' => $user->assignedProjects()->count(),
            'total_hours_tracked' => $user->timeTracking()->sum('duration_seconds') / 3600,
        ];
    }
}
