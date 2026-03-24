# Notification API Documentation

Manage user notifications, send manual notifications, and track notification status. All requests must be authenticated via Sanctum (`Authorization: Bearer {token}`).

---

## Overview

The notification system supports:
- **Automatic notifications** - Triggered by system events (task assignments, messages, status updates)
- **Manual notifications** - Sent by administrators to users
- **Delivery channels** - Database (default) and Email (optional)
- **Read/Unread tracking** - Mark notifications as read individually or in bulk

---

## 📬 Get User Notifications

### List Notifications
**GET** `/api/notifications`

Retrieve notifications for the authenticated user.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `include_read` | boolean | `false` | Include read notifications in results |

**Request:**
```http
GET /api/notifications?include_read=true
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "notifications": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "type": "task_assigned",
      "title": "New Task Assigned: Website Redesign",
      "message": "You have been assigned a new task.",
      "data": {
        "task_id": 15,
        "task_title": "Website Redesign",
        "project_id": 3,
        "project_name": "Client Portal",
        "priority": "high",
        "deadline": "2024-04-15"
      },
      "read_at": null,
      "created_at": "2024-03-22T10:30:00.000000Z"
    },
    {
      "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
      "type": "custom",
      "title": "Meeting Reminder",
      "message": "Team meeting at 3 PM today",
      "data": {},
      "read_at": "2024-03-22T14:00:00.000000Z",
      "created_at": "2024-03-22T09:00:00.000000Z"
    }
  ]
}
```

---

## 📤 Send Manual Notification

**POST** `/api/notifications/send`

Send a custom notification to one or more users. **Requires admin role.**

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | integer | ⚠️* | Single user ID (use either `user_id` or `user_ids`) |
| `user_ids` | array | ⚠️* | Multiple user IDs for broadcast |
| `title` | string | ✅ | Notification title (max 255 chars) |
| `message` | string | ✅ | Notification message body |
| `type` | string | ❌ | Notification type (default: `"custom"`) |
| `data` | object | ❌ | Additional custom data |
| `send_email` | boolean | ❌ | Also send via email (default: `false`) |

⚠️ *Either `user_id` or `user_ids` must be provided.

**Request (Single User):**
```http
POST /api/notifications/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 5,
  "title": "Project Deadline Update",
  "message": "The deadline for Project Alpha has been extended to April 30th.",
  "type": "deadline_update",
  "data": {
    "project_id": 12,
    "old_deadline": "2024-04-15",
    "new_deadline": "2024-04-30"
  },
  "send_email": true
}
```

**Request (Broadcast to Multiple Users):**
```http
POST /api/notifications/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_ids": [1, 5, 8, 12],
  "title": "System Maintenance",
  "message": "Scheduled maintenance tonight from 11 PM to 2 AM. Expect brief downtime.",
  "type": "announcement",
  "send_email": false
}
```

**Response (200 OK):**
```json
{
  "message": "Notification sent successfully",
  "recipients_count": 4
}
```

**Response (403 Forbidden) - Non-admin user:**
```json
{
  "message": "Only administrators can send manual notifications"
}
```

**Response (422 Unprocessable Entity) - Validation error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "user_id": [
      "Either user_id or user_ids must be provided"
    ]
  }
}
```

---

## ✅ Mark Notification as Read

### Mark Single Notification
**POST** `/api/notifications/{id}/read`

Mark a specific notification as read.

**Request:**
```http
POST /api/notifications/a1b2c3d4-e5f6-7890-abcd-ef1234567890/read
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Notification marked as read"
}
```

---

### Mark All Notifications as Read
**POST** `/api/notifications/read-all`

Mark all unread notifications for the authenticated user as read.

**Request:**
```http
POST /api/notifications/read-all
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "All notifications marked as read",
  "count": 12
}
```

---

## 🔢 Get Unread Count

**GET** `/api/notifications/unread-count`

Get the count of unread notifications for the authenticated user.

**Request:**
```http
GET /api/notifications/unread-count
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "unread_count": 7
}
```

---

## 📋 Notification Types

### System-Generated Notifications

| Type | Trigger | Data Fields |
|------|---------|-------------|
| `task_assigned` | User assigned to a task | `task_id`, `task_title`, `project_id`, `project_name`, `priority`, `deadline` |
| `new_message` | User receives a message | `message_id`, `sender_id`, `sender_name`, `preview` |
| `task_status_updated` | Task status changes | `task_id`, `task_title`, `old_status`, `new_status`, `updated_by` |

### Manual Notifications

| Type | Description |
|------|-------------|
| `custom` | Default type for manual notifications |
| `announcement` | System-wide or group announcements |
| `reminder` | Reminder notifications |
| `alert` | Urgent alerts requiring attention |
| *any custom string* | You can define your own types |

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "Only administrators can send manual notifications"
}
```

### 404 Not Found
```json
{
  "message": "Notification not found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message here"]
  }
}
```

---

## Usage Examples

### JavaScript (Axios)
```javascript
// Get unread count
const { data } = await axios.get('/api/notifications/unread-count', {
  headers: { Authorization: `Bearer ${token}` }
});
console.log(`You have ${data.unread_count} unread notifications`);

// Send notification (admin only)
await axios.post('/api/notifications/send', {
  user_id: 5,
  title: 'Task Reminder',
  message: 'Don\'t forget to submit your timesheet',
  send_email: true
}, {
  headers: { Authorization: `Bearer ${token}` }
});

// Mark all as read
await axios.post('/api/notifications/read-all', {}, {
  headers: { Authorization: `Bearer ${token}` }
});
```

### cURL
```bash
# Get notifications
curl -X GET "http://localhost:8000/api/notifications?include_read=false" \
  -H "Authorization: Bearer {token}"

# Send notification
curl -X POST "http://localhost:8000/api/notifications/send" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"user_id":5,"title":"Hello","message":"Test notification"}'

# Mark as read
curl -X POST "http://localhost:8000/api/notifications/{id}/read" \
  -H "Authorization: Bearer {token}"
```

---

## Database Schema

Notifications are stored in the `notifications` table:

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `notifiable_type` | string | Model class (e.g., `App\\Models\\User`) |
| `notifiable_id` | bigint | User ID |
| `type` | string | Notification type |
| `data` | JSON | Notification payload |
| `read_at` | timestamp | When marked as read (null = unread) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

---

## Related Documentation

- [Chat API Docs](./CHAT_API_DOCS.md) - Real-time messaging
- [API README](./API_README.md) - General API overview
