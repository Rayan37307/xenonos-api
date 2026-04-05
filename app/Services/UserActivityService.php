<?php

namespace App\Services;

use App\Models\File;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ServiceOrder;
use App\Models\Task;
use App\Models\TaskTimeTracking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class UserActivityService
{
    /**
     * Paginate activity entries relevant to this user: actions they caused,
     * their account as subject, and records tied to their tasks, projects,
     * invoices, orders, time tracking, and uploads.
     */
    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $taskIds = Task::query()
            ->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->pluck('id');

        $projectIds = Project::query()
            ->where(function ($q) use ($user) {
                $q->whereHas('client', fn ($c) => $c->where('user_id', $user->id))
                    ->orWhereHas('workers', fn ($w) => $w->where('users.id', $user->id));
            })
            ->pluck('id');

        $invoiceIds = Invoice::query()
            ->whereHas('client', fn ($c) => $c->where('user_id', $user->id))
            ->pluck('id');

        $serviceOrderIds = ServiceOrder::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $timeTrackingIds = TaskTimeTracking::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $fileIds = File::query()
            ->where('uploaded_by', $user->id)
            ->pluck('id');

        return Activity::query()
            ->with('causer:id,name,email')
            ->where(function ($outer) use ($user, $taskIds, $projectIds, $invoiceIds, $serviceOrderIds, $timeTrackingIds, $fileIds) {
                $outer->where(function ($q) use ($user) {
                    $q->where('causer_id', $user->id)
                        ->where('causer_type', User::class);
                });

                $outer->orWhere(function ($q) use ($user) {
                    $q->where('subject_id', $user->id)
                        ->where('subject_type', User::class);
                });

                if ($taskIds->isNotEmpty()) {
                    $outer->orWhere(function ($q) use ($taskIds) {
                        $q->where('subject_type', Task::class)
                            ->whereIn('subject_id', $taskIds);
                    });
                }

                if ($projectIds->isNotEmpty()) {
                    $outer->orWhere(function ($q) use ($projectIds) {
                        $q->where('subject_type', Project::class)
                            ->whereIn('subject_id', $projectIds);
                    });
                }

                if ($invoiceIds->isNotEmpty()) {
                    $outer->orWhere(function ($q) use ($invoiceIds) {
                        $q->where('subject_type', Invoice::class)
                            ->whereIn('subject_id', $invoiceIds);
                    });
                }

                if ($serviceOrderIds->isNotEmpty()) {
                    $outer->orWhere(function ($q) use ($serviceOrderIds) {
                        $q->where('subject_type', ServiceOrder::class)
                            ->whereIn('subject_id', $serviceOrderIds);
                    });
                }

                if ($timeTrackingIds->isNotEmpty()) {
                    $outer->orWhere(function ($q) use ($timeTrackingIds) {
                        $q->where('subject_type', TaskTimeTracking::class)
                            ->whereIn('subject_id', $timeTrackingIds);
                    });
                }

                if ($fileIds->isNotEmpty()) {
                    $outer->orWhere(function ($q) use ($fileIds) {
                        $q->where('subject_type', File::class)
                            ->whereIn('subject_id', $fileIds);
                    });
                }
            })
            ->latest()
            ->paginate($perPage);
    }
}
