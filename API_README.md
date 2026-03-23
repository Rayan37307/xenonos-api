# Xenon OS - API Documentation

A scalable Laravel backend API for managing clients, workers, tasks, projects, and communication.

## Tech Stack

- **Laravel 12** - PHP Framework
- **MySQL/PostgreSQL/SQLite** - Database
- **Laravel Sanctum** - API Authentication
- **Laravel Reverb** - WebSocket Server (Realtime)
- **Redis** - Cache & Queue
- **Spatie Packages** - Permissions, Activity Log, Media Library

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- Redis (for queues and cache)
- SQLite/MySQL/PostgreSQL

### Setup Steps

1. **Clone the repository**
```bash
git clone <repository-url>
cd tzsos
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update database configuration**
```env
DB_CONNECTION=sqlite
# Or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=xenon_os
# DB_USERNAME=root
# DB_PASSWORD=
```

5. **Run migrations and seeders**
```bash
php artisan migrate --seed
```

6. **Start development servers**
```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work --async

# Terminal 3: Reverb WebSocket server
php artisan reverb:start

# Terminal 4: Vite development
npm run dev
```

## Default Users

After seeding, you can login with:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@xenon.com | password |
| Client | client@xenon.com | password |
| Worker | alice@xenon.com | password |

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/register` | Register new user | No |
| POST | `/api/auth/login` | Login user | No |
| POST | `/api/auth/logout` | Logout user | Yes |
| GET | `/api/auth/me` | Get current user | Yes |
| PUT | `/api/auth/profile` | Update profile | Yes |
| POST | `/api/auth/avatar` | Update avatar | Yes |

### Users (Admin Only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/users` | List all users |
| GET | `/api/admin/users/{id}` | Get user by ID |
| PUT | `/api/admin/users/{id}` | Update user |
| DELETE | `/api/admin/users/{id}` | Delete user |
| GET | `/api/admin/workers` | List all workers |
| GET | `/api/admin/clients` | List all clients |

### User Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/profile` | Get own profile |
| PUT | `/api/user/profile` | Update own profile |
| GET | `/api/user/activity-summary` | Get activity summary |

### Projects

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/projects` | List all projects |
| POST | `/api/projects` | Create project |
| GET | `/api/projects/{id}` | Get project details |
| PUT | `/api/projects/{id}` | Update project |
| DELETE | `/api/projects/{id}` | Delete project |
| POST | `/api/projects/{id}/workers` | Assign workers |
| DELETE | `/api/projects/{projectId}/workers/{workerId}` | Remove worker |
| GET | `/api/projects/{id}/statistics` | Get project stats |

### Tasks

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tasks` | List all tasks |
| POST | `/api/tasks` | Create task |
| GET | `/api/tasks/{id}` | Get task details |
| PUT | `/api/tasks/{id}` | Update task |
| DELETE | `/api/tasks/{id}` | Delete task |
| POST | `/api/tasks/{id}/assign` | Assign to worker |
| POST | `/api/tasks/{id}/progress` | Update progress |
| GET | `/api/tasks/my` | Get my tasks |
| GET | `/api/projects/{projectId}/tasks/kanban` | Get Kanban board |
| POST | `/api/projects/{projectId}/tasks/reorder` | Reorder tasks |

### Time Tracking

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/tasks/{id}/start-timer` | Start timer |
| POST | `/api/tasks/{id}/stop-timer` | Stop timer |
| GET | `/api/tasks/{id}/time-logs` | Get time logs |
| GET | `/api/time-tracking/my-logs` | Get my logs |

### Chat

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/chat/conversations` | Get conversations |
| GET | `/api/chat/messages/private/{userId}` | Get private messages |
| GET | `/api/chat/messages/project/{projectId}` | Get project messages |
| POST | `/api/chat/messages/private` | Send private message |
| POST | `/api/chat/messages/project/{projectId}` | Send project message |
| POST | `/api/chat/messages/{id}/read` | Mark as read |
| POST | `/api/chat/messages/read-all` | Mark all as read |
| GET | `/api/chat/unread-count` | Get unread count |
| POST | `/api/chat/typing` | Send typing indicator |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | Get notifications |
| POST | `/api/notifications/{id}/read` | Mark as read |
| POST | `/api/notifications/read-all` | Mark all as read |
| GET | `/api/notifications/unread-count` | Get unread count |

### Analytics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/analytics/dashboard` | Get dashboard |
| GET | `/api/analytics/overview` | Get overview (admin) |
| GET | `/api/analytics/recent-projects` | Get recent projects |
| GET | `/api/analytics/active-tasks` | Get active tasks |
| GET | `/api/analytics/revenue-stats` | Get revenue (admin) |
| GET | `/api/analytics/worker-productivity` | Get productivity (admin) |

## Request Examples

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "client",
    "company_name": "Acme Corp"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@xenon.com",
    "password": "password"
  }'
```

### Create Project
```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Website Redesign",
    "description": "Complete website redesign",
    "client_id": 1,
    "status": "active",
    "budget": 15000,
    "deadline": "2024-06-01"
  }'
```

### Create Task
```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "Design homepage",
    "description": "Create homepage mockup",
    "project_id": 1,
    "assigned_to": 2,
    "priority": "high",
    "deadline": "2024-05-15"
  }'
```

## WebSocket Events

### Client-side Events to Listen For

```javascript
// Message sent
Echo.private(`chat.${userId}`)
    .listen('MessageSent', (data) => {
        console.log('New message:', data);
    });

// Task assigned
Echo.private(`chat.${userId}`)
    .listen('TaskAssigned', (data) => {
        console.log('Task assigned:', data);
    });

// Task status updated
Echo.channel(`project.${projectId}`)
    .listen('TaskStatusUpdated', (data) => {
        console.log('Task status updated:', data);
    });

// User typing
Echo.private(`chat.${userId}`)
    .listen('UserTyping', (data) => {
        console.log('User typing:', data);
    });
```

### Broadcasting Typing Indicator

```javascript
Echo.post('/api/chat/typing', {
    receiver_id: userId,
    project_id: projectId,
    is_typing: true
});
```

## Role-Based Access Control

### Admin
- Full access to all features
- Manage users, projects, tasks
- View analytics and revenue

### Client
- View own projects
- Chat with workers
- View project progress
- Download files

### Worker
- View assigned tasks
- Update task progress
- Track time
- Chat with team

## File Upload

### Upload Task Attachment
```bash
curl -X POST http://localhost:8000/api/tasks/1/files \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/file.pdf"
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=AuthTest

# Run with coverage
php artisan test --coverage
```

## Queue Configuration

For production, use Redis for queues:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

Start queue worker:
```bash
php artisan queue:work --tries=3
```

## Production Deployment

1. Set `APP_DEBUG=false`
2. Use MySQL/PostgreSQL instead of SQLite
3. Configure Redis for cache and queues
4. Set up Supervisor for queue workers
5. Use SSL for Reverb WebSocket server
6. Run migrations: `php artisan migrate --force`
7. Optimize: `php artisan optimize`

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/    # API Controllers
│   ├── Middleware/         # Custom middleware
│   └── Resources/          # API Resources
├── Models/                 # Eloquent models
├── Services/               # Business logic
├── Events/                 # Broadcast events
└── Notifications/          # Notification classes

database/
├── migrations/             # Database migrations
├── seeders/                # Database seeders
└── factories/              # Model factories

routes/
└── api.php                 # API routes
```

## Support

For issues or questions, contact the Xenon Studios team.
