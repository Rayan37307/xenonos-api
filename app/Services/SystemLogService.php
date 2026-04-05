<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class SystemLogService
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return Activity::query()
            ->with(['causer', 'subject'])
            ->when(!empty($filters['log_name']), fn (Builder $q, $logName) => $q->where('log_name', $logName))
            ->when(!empty($filters['event']), fn (Builder $q, $event) => $q->where('event', $event))
            ->when(!empty($filters['causer_type']), fn (Builder $q, $causerType) => $q->where('causer_type', $causerType))
            ->when(!empty($filters['subject_type']), fn (Builder $q, $subjectType) => $q->where('subject_type', $subjectType))
            ->when(!empty($filters['causer_id']), fn (Builder $q, $causerId) => $q->where('causer_id', $causerId))
            ->when(!empty($filters['subject_id']), fn (Builder $q, $subjectId) => $q->where('subject_id', $subjectId))
            ->when(!empty($filters['search']), function (Builder $q, $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('properties', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['date_from']), fn (Builder $q, $dateFrom) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when(!empty($filters['date_to']), fn (Builder $q, $dateTo) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Activity
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->find($id);
    }

    public function delete(int $id): bool
    {
        $activity = Activity::query()->find($id);

        if (!$activity) {
            return false;
        }

        return $activity->delete();
    }

    public function getStats(): array
    {
        return [
            'total_logs' => Activity::query()->count(),
            'logs_by_log_name' => Activity::query()
                ->selectRaw('log_name, COUNT(*) as count')
                ->groupBy('log_name')
                ->pluck('count', 'log_name')
                ->toArray(),
            'logs_by_event' => Activity::query()
                ->selectRaw('event, COUNT(*) as count')
                ->whereNotNull('event')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'recent_count' => Activity::query()
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
        ];
    }
}
