<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Services\UserActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserActivityController extends Controller
{
    public function __construct(
        private UserActivityService $userActivityService
    ) {}

    /**
     * Paginated activity log for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 20);

        $paginator = $this->userActivityService->paginateForUser($request->user(), $perPage);

        return ActivityLogResource::collection($paginator);
    }
}
