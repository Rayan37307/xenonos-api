# Milestone 5 — Project Management Core

## Overview

Milestone 5 enhances the **Project Management System** with improved CRUD operations, pagination, filtering, sorting, and a dedicated endpoint for workers to view their assigned projects.

---

## Features Implemented

### 1. Project CRUD Operations

All existing CRUD operations were enhanced with pagination:

| Operation | Endpoint | Method | Status |
|------------|----------|--------|--------|
| List projects | `/api/projects` | GET | ✅ Enhanced with pagination |
| View project | `/api/projects/{id}` | GET | ✅ Existing |
| Create project | `/api/projects` | POST | ✅ Existing |
| Update project | `/api/projects/{id}` | PUT | ✅ Existing |
| Delete project | `/api/projects/{id}` | DELETE | ✅ Existing |
| Assign workers | `/api/projects/{id}/workers` | POST | ✅ Existing |
| Remove worker | `/api/projects/{projectId}/workers/{workerId}` | DELETE | ✅ Existing |
| Project stats | `/api/projects/{id}/statistics` | GET | ✅ Existing |
| **My Projects** | `/api/projects/my` | GET | ✅ NEW |

### 2. Pagination & Filtering

The `GET /api/projects` endpoint now supports pagination and filtering:

**Query Parameters:**
- `?page={n}` — Page number
- `?per_page={n}` — Items per page (default: 15)
- `?status={status}` — Filter by status
- `?client_id={id}` — Filter by client
- `?search={term}` — Search by name
- `?worker_id={id}` — Filter by assigned worker
- `?sort_field={field}` — Sort by name, status, budget, deadline, created_at
- `?sort_direction={asc|desc}` — Sort direction

**Response:**
```json
{
  "projects": [...],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

### 3. My Projects Endpoint (NEW)

Workers can now view their assigned projects via:

```
GET /api/projects/my?status=active&search=web
```

**Features:**
- Returns only projects where the authenticated user is assigned as a worker
- Supports filtering by status and search
- Paginated response
- Accessible to all authenticated users (workers and clients)

**Response:**
```json
{
  "projects": [
    {
      "id": 5,
      "name": "Website Redesign",
      "status": "active",
      "client": { ... },
      "workers": [...],
      "tasks": [...]
    }
  ],
  "meta": { ... }
}
```

### 4. Enhanced ProjectService

Key improvements to `ProjectService`:

- **Pagination support** — Returns `LengthAwarePaginator` instead of Collection
- **Worker filtering** — Filter projects by assigned worker ID
- **Sorting** — Sort by any allowed field
- **Worker projects** — New method `getWorkerProjects()` returns paginated projects where user is assigned

---

## API Endpoints Detail

### List All Projects (Admin/All)
```
GET /api/projects?status=active&client_id=1&search=website&page=1&per_page=15&sort_field=name&sort_direction=asc
```

### Get My Assigned Projects (Worker)
```
GET /api/projects/my?status=active&search=&page=1
```

### Create Project
```
POST /api/projects
{
  "name": "New Project",
  "description": "Project description",
  "client_id": 1,
  "status": "planning",
  "budget": 5000,
  "deadline": "2026-05-01"
}
```

### Assign Workers
```
POST /api/projects/1/workers
{
  "worker_ids": [2, 3, 4]
}
```

---

## Frontend Updates

### API Service (`src/services/api.js`)
Added new function:
```javascript
export async function getMyProjects(params = {}) {
  const response = await api.get('/projects/my', { params })
  return response.data
}
```

### Projects Store (`src/stores/projects.js`)
- Added `pagination` state
- Added `fetchMyProjects()` action
- Updated `fetchProjects()` to handle paginated response

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Services/ProjectService.php` | Added pagination, worker filtering, sorting, `getWorkerProjects()` |
| `app/Http/Controllers/Api/Project/ProjectController.php` | Added `myProjects()` endpoint, pagination in `index()` |
| `routes/api.php` | Added `/projects/my` route |
| `src/services/api.js` | Added `getMyProjects()` |
| `src/stores/projects.js` | Added pagination, `fetchMyProjects()` |

---

## Testing

Test the endpoints:

```bash
# List all projects
curl -H "Authorization: Bearer {token}" "http://localhost:8000/api/projects?per_page=5"

# Get my projects
curl -H "Authorization: Bearer {token}" "http://localhost:8000/api/projects/my"

# Filter by status
curl -H "Authorization: Bearer {token}" "http://localhost:8000/api/projects?status=active"
```

---

## Related Milestones

- **Milestone 4** — Client Management (projects linked to clients)
- **Milestone 6** — Project Workspace (timeline, files, activity)
- **Milestone 7** — Task Management (tasks linked to projects)
