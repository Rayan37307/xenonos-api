<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Pagination\LengthAwarePaginator;

class AnnouncementService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Announcement::query()->with('user');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPublished(int $perPage = 10): LengthAwarePaginator
    {
        return Announcement::published()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getById(int $id): Announcement
    {
        return Announcement::with('user')->findOrFail($id);
    }

    public function create(array $data): Announcement
    {
        return Announcement::create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'type' => $data['type'] ?? 'info',
            'is_published' => $data['is_published'] ?? true,
            'published_at' => $data['published_at'] ?? ($data['is_published'] ? now() : null),
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->update([
            'title' => $data['title'] ?? $announcement->title,
            'content' => $data['content'] ?? $announcement->content,
            'type' => $data['type'] ?? $announcement->type,
            'is_published' => $data['is_published'] ?? $announcement->is_published,
            'published_at' => $data['published_at'] ?? $announcement->published_at,
            'expires_at' => $data['expires_at'] ?? $announcement->expires_at,
        ]);

        return $announcement->fresh();
    }

    public function delete(Announcement $announcement): bool
    {
        return $announcement->delete();
    }

    public function publish(Announcement $announcement): Announcement
    {
        $announcement->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return $announcement->fresh();
    }

    public function unpublish(Announcement $announcement): Announcement
    {
        $announcement->update([
            'is_published' => false,
        ]);

        return $announcement->fresh();
    }
}
