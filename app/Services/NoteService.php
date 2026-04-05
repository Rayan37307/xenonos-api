<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Note::query()->with('user');

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getById(int $id): Note
    {
        return Note::with('user')->findOrFail($id);
    }

    public function create(array $data): Note
    {
        return Note::create([
            'user_id' => $data['user_id'],
            'noteable_type' => $data['noteable_type'] ?? null,
            'noteable_id' => $data['noteable_id'] ?? null,
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'color' => $data['color'] ?? '#3b82f6',
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);
    }

    public function update(Note $note, array $data): Note
    {
        $note->update([
            'title' => $data['title'] ?? $note->title,
            'content' => $data['content'] ?? $note->content,
            'color' => $data['color'] ?? $note->color,
            'is_pinned' => $data['is_pinned'] ?? $note->is_pinned,
        ]);

        return $note->fresh();
    }

    public function delete(Note $note): bool
    {
        return $note->delete();
    }

    public function togglePin(Note $note): Note
    {
        $note->update(['is_pinned' => !$note->is_pinned]);
        return $note->fresh();
    }

    public function getForEntity(string $type, int $id): Collection
    {
        return Note::where('noteable_type', $type)
            ->where('noteable_id', $id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserNotes(int $userId): Collection
    {
        return Note::where('user_id', $userId)
            ->whereNull('noteable_type')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
