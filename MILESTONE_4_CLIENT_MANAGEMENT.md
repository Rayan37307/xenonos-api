# Milestone 4 — Client Management System

## Overview

Milestone 4 implements a complete **Client Management System** for the XenonOS backend. This system provides full CRUD (Create, Read, Update, Delete) functionality for client accounts, along with advanced features like pagination, search, filtering, statistics, and relationship management with projects, invoices, and service orders.

---

## Features Implemented

### 1. Client CRUD Operations

| Operation | Endpoint | Method | Description |
|------------|----------|--------|-------------|
| List clients | `/api/admin/clients` | GET | Paginated list with search & filters |
| View client | `/api/admin/clients/{id}` | GET | Single client with relationships |
| Create client | `/api/admin/clients` | POST | Creates user + client profile |
| Update client | `/api/admin/clients/{id}` | PUT | Updates client + user data |
| Delete client | `/api/admin/clients/{id}` | DELETE | Soft delete client |
| Client stats | `/api/admin/clients/{id}/stats` | GET | Aggregated statistics |

### 2. Database Enhancements

Added new columns to the `clients` table:

```php
$table->enum('status', ['active', 'inactive'])->default('active');
$table->text('notes')->nullable();           // Admin notes
$table->softDeletes();                       // Soft delete support

// Indexes for performance
$table->index('company_name');
$table->index('status');
$table->index('created_at');
```

### 3. Client Model Relationships

```php
// New relationships added
public function invoices(): HasMany
public function serviceOrders(): HasMany
public function activities(): MorphMany  // Spatie Activitylog

// Existing relationships
public function user(): BelongsTo
public function projects(): HasMany
```

### 4. Client Statistics

The client stats include:
- **total_projects** — Total projects count
- **active_projects** — Active projects count
- **completed_projects** — Completed projects count
- **total_invoices** — Total invoices count
- **paid_invoices** — Paid invoices count
- **unpaid_invoices** — Unpaid invoices count
- **total_revenue** — Sum of paid invoices
- **pending_revenue** — Sum of unpaid invoices
- **total_service_orders** — Total service orders
- **pending_service_orders** — Pending service orders
- **completed_service_orders** — Completed service orders
- **client_since** — Client creation date

### 5. Filtering & Search

Supported query parameters:
- `?search={term}` — Search by company name, user name, or email
- `?status=active|inactive` — Filter by status
- `?date_from={date}` — Filter by start date
- `?date_to={date}` — Filter by end date
- `?sort_field={field}` — Sort by company_name, status, or created_at
- `?sort_direction=asc|desc` — Sort direction
- `?page={n}` — Pagination page
- `?per_page={n}` — Items per page (default: 15)

### 6. Authorization

Client operations are protected by the `ClientPolicy`:

| Policy | Admin | Client | Worker |
|--------|-------|--------|--------|
| viewAny | ✓ | ✗ | ✗ |
| view | ✓ (all) | ✓ (own) | ✗ |
| create | ✓ | ✗ | ✗ |
| update | ✓ | ✗ | ✗ |
| delete | ✓ | ✗ | ✗ |

---

## API Endpoints Detail

### List Clients
```
GET /api/admin/clients?search=acme&status=active&page=1&per_page=15
```

Response:
```json
{
  "clients": [
    {
      "id": 1,
      "user_id": 5,
      "company_name": "Acme Corp",
      "phone": "+1234567890",
      "address": "123 Main St",
      "status": "active",
      "notes": "Important client",
      "created_at": "2026-04-05T10:00:00+00:00",
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@acme.com",
        "avatar": "/media/avatars/..."
      },
      "projects_count": 3,
      "invoices_count": 5,
      "service_orders_count": 2
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

### Create Client
```
POST /api/admin/clients
Content-Type: application/json

{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "company_name": "Tech Solutions",
  "phone": "+1987654321",
  "address": "456 Oak Ave",
  "status": "active",
  "notes": "VIP client"
}
```

Response (201):
```json
{
  "message": "Client created successfully",
  "client": { ... }
}
```

### Client Stats
```
GET /api/admin/clients/1/stats
```

Response:
```json
{
  "stats": {
    "total_projects": 5,
    "active_projects": 2,
    "completed_projects": 3,
    "total_invoices": 12,
    "paid_invoices": 10,
    "unpaid_invoices": 2,
    "total_revenue": 25000.00,
    "pending_revenue": 5000.00,
    "total_service_orders": 4,
    "pending_service_orders": 1,
    "completed_service_orders": 3,
    "client_since": "2026-01-15T00:00:00+00:00"
  }
}
```

---

## Frontend Features

### Clients List Page (`/clients`)
- Search bar with debounced search
- Status filter tabs (All, Active, Inactive)
- Data table with columns: Client, Company, Status, Projects, Invoices, Joined, Actions
- Pagination controls
- Create client modal with form
- Delete confirmation modal

### Client Details Page (`/clients/:id`)
- Client overview with stats cards
- Tabbed interface:
  - **Overview** — Contact info, admin notes
  - **Projects** — List of client projects
  - **Invoices** — List of client invoices
  - **Service Orders** — List of service orders

### Sidebar Navigation
- New "Clients" nav link added between Projects and Files

---

## Files Created/Modified

### Backend
| File | Action |
|------|--------|
| `app/Models/Client.php` | Modified — Added relationships, SoftDeletes, LogsActivity |
| `app/Services/ClientService.php` | Created — Full business logic |
| `app/Http/Controllers/Api/Client/ClientController.php` | Created — CRUD + stats |
| `app/Http/Resources/ClientResource.php` | Created — API resource |
| `app/Policies/ClientPolicy.php` | Created — Authorization policy |
| `app/Http/Requests/Client/StoreClientRequest.php` | Created — Validation |
| `app/Http/Requests/Client/UpdateClientRequest.php` | Created — Validation |
| `app/Providers/AppServiceProvider.php` | Modified — Registered policy |
| `database/migrations/2026_04_05_000002_*.php` | Created — Schema enhancements |
| `database/factories/ClientFactory.php` | Modified — Added status |
| `routes/api.php` | Modified — Added client routes |
| `tests/Feature/Client/ClientTest.php` | Created — Feature tests |

### Frontend
| File | Action |
|------|--------|
| `src/services/api.js` | Modified — Added client API functions |
| `src/stores/clients.js` | Created — Pinia store |
| `src/views/Clients.vue` | Created — List view |
| `src/views/ClientDetails.vue` | Created — Detail view |
| `src/router/index.js` | Modified — Added routes |
| `src/components/AppSidebar.vue` | Modified — Added nav link |

---

## Dependencies

- **Laravel Sanctum** — API authentication
- **Spatie Roles & Permissions** — Role management
- **Spatie Activitylog** — Activity tracking
- **Spatie Media Library** — File attachments (future)

---

## Testing

Run feature tests:
```bash
php artisan test --filter=Client
```

Test coverage includes:
- Admin can list clients
- Admin can search clients
- Admin can filter clients by status
- Admin can view single client
- Admin can create client (creates user + profile)
- Admin can update client
- Admin can delete client (soft delete)
- Admin can view client statistics
- Non-admin users cannot access endpoints
- Validation errors on missing required fields
