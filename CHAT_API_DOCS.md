# Chat & Messaging API Documentation

This document outlines the available endpoints for the newly refactored Chat and Messaging system. All requests must be authenticated via Sanctum (`Authorization: Bearer {token}`).

---

## 📌 Channels

### 1. List Channels
**GET** `/api/chat/channels`

Returns all public channels and any private channels the authenticated user is a member of.

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "General",
      "description": "General discussions",
      "type": "public",
      "unread_count": 0,
      "last_message": "Hello everyone!",
      "last_message_at": "2024-03-22T14:30:00.000000Z",
      "created_at": "2024-03-22T10:00:00.000000Z",
      "members_count": 5
    }
  ]
}
```

### 2. Create Channel
**POST** `/api/chat/channels`

**Payload:**
```json
{
  "name": "Design Team",
  "description": "Discussions for the design team",
  "type": "private",
  "member_ids": [2, 3, 4]
}
```

**Response (201 Created):**
Returns the newly created Channel object.

### 3. Get Channel Details
**GET** `/api/chat/channels/{id}`

Returns standard Channel object schema.

### 4. Update Channel
**PUT** `/api/chat/channels/{id}`

**Payload:**
```json
{
  "name": "Design & UI Team",
  "description": "Updated description"
}
```

### 5. Delete Channel
**DELETE** `/api/chat/channels/{id}`

**Response (200 OK):**
```json
{
  "message": "Channel deleted"
}
```

---

## 👥 Channel Members

### 6. Add Member
**POST** `/api/chat/channels/{id}/members`

**Payload:**
```json
{
  "user_id": 5
}
```

### 7. Remove Member
**DELETE** `/api/chat/channels/{id}/members/{userId}`

---

## 💬 Channel Messages

### 8. Get Channel Messages
**GET** `/api/chat/channels/{id}/messages?page=1&per_page=20`

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 10,
      "channel_id": 1,
      "conversation_id": null,
      "user_id": 2,
      "user": {
        "id": 2,
        "name": "Sarah Jenkins",
        "avatar": "http://localhost/storage/avatars/sarah.jpg",
        "role": "designer"
      },
      "content": "I've just uploaded the updated design assets for the dashboard.",
      "attachments": [],
      "reactions": [],
      "read_by": [],
      "created_at": "2024-03-22T14:30:00.000000Z",
      "updated_at": "2024-03-22T14:30:00.000000Z"
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45
  }
}
```

### 9. Send Channel Message
**POST** `/api/chat/channels/{id}/messages`

*(Supports `multipart/form-data` if uploading attachments)*

**Payload:**
- `content` (string, required)
- `attachments[]` (file, optional)

---

## 📩 Direct Messages (Conversations)

### 10. List Conversations
**GET** `/api/chat/conversations`

Returns the recent DM threads the user is part of.

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 3,
        "name": "Mike Ross",
        "avatar": null,
        "role": "developer"
      },
      "unread_count": 0,
      "last_message": "Can you check the PR?",
      "last_message_at": "2024-03-22T15:00:00.000000Z",
      "created_at": "2024-03-20T09:00:00.000000Z"
    }
  ]
}
```

### 11. Start / Get Conversation
**POST** `/api/chat/conversations`

Finds an existing conversation with the specified user or creates a new one.

**Payload:**
```json
{
  "user_id": 3
}
```

### 12. Get Conversation Messages
**GET** `/api/chat/conversations/{id}/messages?page=1`

Returns paginated messages exactly matching the Channel Messages schema.

### 13. Send Direct Message
**POST** `/api/chat/conversations/{id}/messages`

*(Supports `multipart/form-data`)*
Payload identical to Channel Message sending (`content`, `attachments[]`).

---

## 🛠 Message Utilities

### 14. Edit Message
**PUT** `/api/chat/messages/{id}`

**Payload:**
```json
{
  "content": "Updated message text"
}
```

### 15. Delete Message
**DELETE** `/api/chat/messages/{id}`

### 16. Mark as Read
**POST** `/api/chat/messages/{id}/read`

---

## 😊 Reactions

### 17. Add Reaction
**POST** `/api/chat/messages/{id}/reactions`

**Payload:**
```json
{
  "emoji": "👍"
}
```

### 18. Remove Reaction
**DELETE** `/api/chat/messages/{id}/reactions/{emoji}`

---

## 🌐 Global Chat Tools

### 19. Online Status
**GET** `/api/chat/users/online-status?user_ids[]=2&user_ids[]=3`

**Response (200 OK):**
```json
{
  "data": {
    "2": "online",
    "3": "online"
  }
}
```

### 20. Global Message Search
**GET** `/api/chat/search?q=design`

Searches through all channels and DMs the authenticated user has access to. Returns standard message array.

### 21. Standard Upload
**POST** `/api/chat/upload`

Alternative endpoint if you simply want to upload a file and get a URL before sending a message.

**Payload:** (multipart/form-data)
- `file` (file, required)

**Response:**
```json
{
  "url": "http://localhost/storage/chat-uploads/xyz.png",
  "name": "xyz.png"
}
```
