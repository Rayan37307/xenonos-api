<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search', 'date_from', 'date_to', 'sort_field', 'sort_direction']);
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->clientService->getAll($filters, $perPage);

        return response()->json([
            'clients' => ClientResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Client $client): JsonResponse
    {
        $client = $this->clientService->getById($client->id);

        return response()->json([
            'client' => new ClientResource($client),
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return response()->json([
            'message' => 'Client created successfully',
            'client' => new ClientResource($client),
        ], 201);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client = $this->clientService->update($client, $request->validated());

        return response()->json([
            'message' => 'Client updated successfully',
            'client' => new ClientResource($client),
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->clientService->delete($client);

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }

    public function stats(Client $client): JsonResponse
    {
        $stats = $this->clientService->getStats($client);

        return response()->json([
            'stats' => $stats,
        ]);
    }
}
