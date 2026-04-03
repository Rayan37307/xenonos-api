# Signup Invite API Documentation

## Overview

The Signup Invite system allows administrators to create unique, one-time-use invite links for user registration. Only users with the "admin" role can create invite links.

## Features

- **Admin-only invite creation**: Only administrators can generate signup invite links
- **One-time use**: Each invite link can only be used once for registration
- **Optional expiration**: Invites can be set to expire after a specified number of hours
- **Tracking**: Full audit trail of who created the invite, who used it, and when

## API Endpoints

### Public Endpoints (No Authentication Required)

#### 1. Validate Invite Token

Before showing the signup form, validate the invite token.

**Endpoint:** `GET /api/signup-invites/{token}`

**Response (Valid Token):**
```json
{
  "valid": true,
  "message": "Invite token is valid.",
  "invite": {
    "token": "550e8400-e29b-41d4-a716-446655440000",
    "expires_at": "2026-04-01T12:00:00.000000Z",
    "creator_name": "Admin User"
  }
}
```

**Response (Invalid/Used/Expired Token):**
```json
{
  "valid": false,
  "message": "This invite link has already been used."
}
```

**Status Codes:**
- `200`: Valid token
- `400`: Invalid, used, or expired token

---

#### 2. Register with Invite Token

Register a new user account using a valid invite token.

**Endpoint:** `POST /api/signup-invites/{token}/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "password_confirmation": "securepassword123",
  "phone_number": "+1234567890",
  "profile_image_link": "https://example.com/avatar.jpg"
}
```

**Response (Success):**
```json
{
  "message": "Registration successful. Welcome!",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "worker"
  },
  "token": "1|abc123..."
}
```

**Response (Invalid Token):**
```json
{
  "message": "Invalid or expired invite token",
  "errors": {
    "token": ["This invite link has already been used."]
  }
}
```

**Status Codes:**
- `201`: Registration successful
- `400`: Invalid or expired token
- `422`: Validation error

---

### Admin-Only Endpoints (Authentication Required)

All admin endpoints require:
- Valid authentication token (Bearer token)
- User must have "admin" role

**Headers:**
```
Authorization: Bearer {admin-token}
```

---

#### 3. Create Invite Link

Generate a new signup invite link.

**Endpoint:** `POST /api/admin/signup-invites`

**Request Body (Optional):**
```json
{
  "expires_in_hours": 24
}
```

**Response:**
```json
{
  "message": "Signup invite created successfully",
  "invite": {
    "id": 1,
    "token": "550e8400-e29b-41d4-a716-446655440000",
    "signup_url": "https://yourapp.com/signup/550e8400-e29b-41d4-a716-446655440000",
    "expires_at": "2026-04-01T12:00:00.000000Z",
    "created_at": "2026-03-31T12:00:00.000000Z",
    "creator": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    }
  }
}
```

**Status Codes:**
- `201`: Invite created successfully
- `401`: Unauthorized (not authenticated)
- `403`: Forbidden (not an admin)
- `422`: Validation error

---

#### 4. List All Invites

Get a list of all signup invites with optional filtering.

**Endpoint:** `GET /api/admin/signup-invites`

**Query Parameters:**
- `filter` (optional): Filter by status
  - `valid`: Only unused, unexpired invites
  - `used`: Only used invites
  - `expired`: Only expired invites
  - (no filter): All invites

**Example:** `GET /api/admin/signup-invites?filter=valid`

**Response:**
```json
{
  "invites": [
    {
      "id": 1,
      "token": "550e8400-e29b-41d4-a716-446655440000",
      "signup_url": "https://yourapp.com/signup/550e8400-e29b-41d4-a716-446655440000",
      "is_used": false,
      "is_expired": false,
      "is_valid": true,
      "used_at": null,
      "expires_at": "2026-04-01T12:00:00.000000Z",
      "created_at": "2026-03-31T12:00:00.000000Z",
      "creator": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com"
      },
      "used_by": null
    },
    {
      "id": 2,
      "token": "660e8400-e29b-41d4-a716-446655440001",
      "signup_url": "https://yourapp.com/signup/660e8400-e29b-41d4-a716-446655440001",
      "is_used": true,
      "is_expired": false,
      "is_valid": false,
      "used_at": "2026-03-31T14:00:00.000000Z",
      "expires_at": null,
      "created_at": "2026-03-31T10:00:00.000000Z",
      "creator": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com"
      },
      "used_by": {
        "id": 5,
        "name": "New User",
        "email": "newuser@example.com"
      }
    }
  ]
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthorized
- `403`: Forbidden

---

#### 5. Get Specific Invite

Get details of a specific invite by token.

**Endpoint:** `GET /api/admin/signup-invites/{token}`

**Response:**
```json
{
  "invite": {
    "id": 1,
    "token": "550e8400-e29b-41d4-a716-446655440000",
    "signup_url": "https://yourapp.com/signup/550e8400-e29b-41d4-a716-446655440000",
    "is_used": false,
    "is_expired": false,
    "is_valid": true,
    "used_at": null,
    "expires_at": "2026-04-01T12:00:00.000000Z",
    "created_at": "2026-03-31T12:00:00.000000Z",
    "creator": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    },
    "used_by": null
  }
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Invite not found

---

#### 6. Delete Invite

Delete an existing invite (used or unused).

**Endpoint:** `DELETE /api/admin/signup-invites/{token}`

**Response:**
```json
{
  "message": "Invite deleted successfully"
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Invite not found

---

## Database Schema

### `signup_invites` Table

| Column       | Type      | Description                              |
|--------------|-----------|------------------------------------------|
| id           | BIGINT    | Primary key                              |
| token        | UUID      | Unique invite token (auto-generated)     |
| creator_id   | BIGINT    | Foreign key to users (admin who created) |
| used_by      | BIGINT    | Foreign key to users (who used it)       |
| used_at      | TIMESTAMP | When the invite was used                 |
| expires_at   | TIMESTAMP | When the invite expires (nullable)       |
| created_at   | TIMESTAMP | Record creation timestamp                |
| updated_at   | TIMESTAMP | Record update timestamp                  |

**Indexes:**
- `token` (unique)
- `(token, expires_at)` (composite)
- `(creator_id, created_at)` (composite)

**Foreign Keys:**
- `creator_id` → `users.id` (ON DELETE CASCADE)
- `used_by` → `users.id` (ON DELETE SET NULL)

---

## Usage Examples

### cURL Examples

#### Create Invite (Admin)
```bash
curl -X POST https://yourapp.com/api/admin/signup-invites \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -d '{"expires_in_hours": 48}'
```

#### Validate Token (Public)
```bash
curl -X GET https://yourapp.com/api/signup-invites/550e8400-e29b-41d4-a716-446655440000
```

#### Register with Token (Public)
```bash
curl -X POST https://yourapp.com/api/signup-invites/550e8400-e29b-41d4-a716-446655440000/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword123",
    "password_confirmation": "securepassword123"
  }'
```

#### List All Invites (Admin)
```bash
curl -X GET https://yourapp.com/api/admin/signup-invites \
  -H "Authorization: Bearer {admin-token}"
```

#### List Only Valid Invites (Admin)
```bash
curl -X GET "https://yourapp.com/api/admin/signup-invites?filter=valid" \
  -H "Authorization: Bearer {admin-token}"
```

---

## Security Considerations

1. **Token Uniqueness**: Each token is a UUID v4, making it cryptographically secure and unpredictable
2. **One-Time Use**: Tokens are invalidated immediately after successful registration
3. **Admin-Only Creation**: Only users with the "admin" role can create invites
4. **Rate Limiting**: Consider implementing rate limiting on the registration endpoint to prevent abuse
5. **HTTPS**: Always use HTTPS in production to protect tokens in transit

---

## Error Handling

All endpoints return consistent error formats:

```json
{
  "message": "Error description",
  "errors": {
    "field": ["Specific error message"]
  }
}
```

Common HTTP status codes:
- `200`: Success (GET, PUT, DELETE)
- `201`: Created (POST)
- `400`: Bad Request (invalid token, business logic error)
- `401`: Unauthorized (not authenticated)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found
- `422`: Unprocessable Entity (validation error)
- `500`: Internal Server Error
