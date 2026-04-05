<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSortFields = ['company_name', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        $query->with(['user'])
            ->withCount(['projects', 'invoices', 'serviceOrders'])
            ->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function getById(int $id): Client
    {
        return Client::with([
            'user',
            'projects' => function ($q) {
                $q->latest();
            },
            'invoices' => function ($q) {
                $q->latest();
            },
            'serviceOrders' => function ($q) {
                $q->latest();
            },
        ])->findOrFail($id);
    }

    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password'] ?? 'password'),
                'role' => 'client',
                'phone_number' => $data['phone'] ?? null,
            ]);

            $user->assignRole('client');

            $client = Client::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            return $client->load('user');
        });
    }

    public function update(Client $client, array $data): Client
    {
        DB::transaction(function () use ($client, $data) {
            $client->update([
                'company_name' => $data['company_name'] ?? $client->company_name,
                'phone' => $data['phone'] ?? $client->phone,
                'address' => $data['address'] ?? $client->address,
                'status' => $data['status'] ?? $client->status,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $client->notes,
            ]);

            if ($client->user) {
                $client->user->update([
                    'name' => $data['name'] ?? $client->user->name,
                    'email' => $data['email'] ?? $client->user->email,
                    'phone_number' => $data['phone'] ?? $client->user->phone_number,
                ]);
            }
        });

        return $client->fresh()->load('user');
    }

    public function delete(Client $client): bool
    {
        return $client->delete();
    }

    public function getStats(Client $client): array
    {
        $totalRevenue = $client->invoices()->where('status', 'paid')->sum('amount');
        $pendingRevenue = $client->invoices()->where('status', 'unpaid')->sum('amount');

        return [
            'total_projects' => $client->projects()->count(),
            'active_projects' => $client->projects()->where('status', 'active')->count(),
            'completed_projects' => $client->projects()->where('status', 'completed')->count(),
            'total_invoices' => $client->invoices()->count(),
            'paid_invoices' => $client->invoices()->where('status', 'paid')->count(),
            'unpaid_invoices' => $client->invoices()->where('status', 'unpaid')->count(),
            'total_revenue' => $totalRevenue,
            'pending_revenue' => $pendingRevenue,
            'total_service_orders' => $client->serviceOrders()->count(),
            'pending_service_orders' => $client->serviceOrders()->where('status', 'pending')->count(),
            'completed_service_orders' => $client->serviceOrders()->where('status', 'completed')->count(),
            'client_since' => $client->created_at->toIso8601String(),
        ];
    }
}
