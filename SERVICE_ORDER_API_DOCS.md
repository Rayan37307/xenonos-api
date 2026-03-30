# Service Order API Documentation

## Overview

The Service Order API allows authenticated users (clients) to submit service order proposals to administrators. Admins can review, approve, reject, and manage these service orders. When a new service order is created, all administrators receive a notification via email and in-app notification.

## Authentication

All endpoints require authentication via Laravel Sanctum tokens. Include the token in the `Authorization` header:

```
Authorization: Bearer {token}
```

## Endpoints

### Submit a Service Order Proposal

**POST** `/api/service-orders`

Submit a new service order proposal. This will notify all administrators.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `service_type` | string | Yes | Type of service requested |
| `title` | string | Yes | Title of the service order |
| `description` | string | Yes | Detailed description of the service |
| `budget_min` | number | No | Minimum budget amount |
| `budget_max` | number | No | Maximum budget amount (must be >= budget_min) |
| `deadline` | date (Y-m-d) | No | Expected deadline for the service |

**Example Request:**

```json
{
  "service_type": "Web Development",
  "title": "E-commerce Website Development",
  "description": "I need a full-featured e-commerce website with payment integration, inventory management, and customer portal.",
  "budget_min": 5000,
  "budget_max": 8000,
  "deadline": "2026-06-30"
}
```

**Success Response (201 Created):**

```json
{
  "message": "Service order submitted successfully. An admin will review your proposal.",
  "service_order": {
    "id": 1,
    "service_type": "Web Development",
    "title": "E-commerce Website Development",
    "description": "I need a full-featured e-commerce website with payment integration, inventory management, and customer portal.",
    "budget_min": 5000.00,
    "budget_max": 8000.00,
    "deadline": "2026-06-30T00:00:00+00:00",
    "status": "pending",
    "admin_notes": null,
    "created_at": "2026-03-30T08:15:00+00:00",
    "updated_at": "2026-03-30T08:15:00+00:00",
    "client": {
      "id": 1,
      "name": "John Doe"
    },
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Get All Service Orders (Admin Only)

**GET** `/api/admin/service-orders`

Retrieve all service orders with optional filtering. Requires `admin` role.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `client_id` | int | Filter by client ID |
| `user_id` | int | Filter by user ID |
| `status` | string | Filter by status (pending, reviewing, approved, rejected) |
| `service_type` | string | Filter by service type |

**Success Response (200 OK):**

```json
{
  "service_orders": [
    {
      "id": 1,
      "service_type": "Web Development",
      "title": "E-commerce Website Development",
      "description": "I need a full-featured e-commerce website...",
      "budget_min": 5000.00,
      "budget_max": 8000.00,
      "deadline": "2026-06-30T00:00:00+00:00",
      "status": "pending",
      "admin_notes": null,
      "created_at": "2026-03-30T08:15:00+00:00",
      "updated_at": "2026-03-30T08:15:00+00:00",
      "client": {
        "id": 1,
        "name": "John Doe"
      },
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  ]
}
```

---

### Get Service Order by ID (Admin Only)

**GET** `/api/admin/service-orders/{id}`

Retrieve a specific service order by ID. Requires `admin` role.

**Success Response (200 OK):**

```json
{
  "service_order": {
    "id": 1,
    "service_type": "Web Development",
    "title": "E-commerce Website Development",
    "description": "I need a full-featured e-commerce website...",
    "budget_min": 5000.00,
    "budget_max": 8000.00,
    "deadline": "2026-06-30T00:00:00+00:00",
    "status": "pending",
    "admin_notes": null,
    "created_at": "2026-03-30T08:15:00+00:00",
    "updated_at": "2026-03-30T08:15:00+00:00",
    "client": {
      "id": 1,
      "name": "John Doe"
    },
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Update Service Order Status (Admin Only)

**POST** `/api/admin/service-orders/{id}/status`

Update the status of a service order. Requires `admin` role.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `status` | string | Yes | New status (pending, reviewing, approved, rejected) |

**Example Request:**

```json
{
  "status": "reviewing"
}
```

**Success Response (200 OK):**

```json
{
  "message": "Service order status updated successfully",
  "service_order": {
    "id": 1,
    "service_type": "Web Development",
    "title": "E-commerce Website Development",
    "description": "I need a full-featured e-commerce website...",
    "budget_min": 5000.00,
    "budget_max": 8000.00,
    "deadline": "2026-06-30T00:00:00+00:00",
    "status": "reviewing",
    "admin_notes": null,
    "created_at": "2026-03-30T08:15:00+00:00",
    "updated_at": "2026-03-30T08:20:00+00:00",
    "client": {
      "id": 1,
      "name": "John Doe"
    },
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Add Admin Notes (Admin Only)

**POST** `/api/admin/service-orders/{id}/notes`

Add internal notes to a service order. Requires `admin` role.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `admin_notes` | string | Yes | Internal notes for the service order |

**Example Request:**

```json
{
  "admin_notes": "Client has a good history. Priority project."
}
```

**Success Response (200 OK):**

```json
{
  "message": "Admin notes added successfully",
  "service_order": {
    "id": 1,
    "service_type": "Web Development",
    "title": "E-commerce Website Development",
    "description": "I need a full-featured e-commerce website...",
    "budget_min": 5000.00,
    "budget_max": 8000.00,
    "deadline": "2026-06-30T00:00:00+00:00",
    "status": "reviewing",
    "admin_notes": "Client has a good history. Priority project.",
    "created_at": "2026-03-30T08:15:00+00:00",
    "updated_at": "2026-03-30T08:25:00+00:00",
    "client": {
      "id": 1,
      "name": "John Doe"
    },
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Update Service Order (Admin Only)

**PUT** `/api/admin/service-orders/{id}`

Update any field of a service order. Requires `admin` role.

**Request Body (all fields optional):**

| Field | Type | Description |
|-------|------|-------------|
| `service_type` | string | Type of service |
| `title` | string | Title of the service order |
| `description` | string | Description of the service |
| `budget_min` | number | Minimum budget |
| `budget_max` | number | Maximum budget |
| `deadline` | date (Y-m-d) | Deadline |
| `status` | string | Status (pending, reviewing, approved, rejected) |
| `admin_notes` | string | Admin notes |

**Success Response (200 OK):**

```json
{
  "message": "Service order updated successfully",
  "service_order": {
    "id": 1,
    "service_type": "Web Development",
    "title": "E-commerce Website Development",
    "description": "Updated description...",
    "budget_min": 5000.00,
    "budget_max": 8000.00,
    "deadline": "2026-06-30T00:00:00+00:00",
    "status": "reviewing",
    "admin_notes": "Updated notes",
    "created_at": "2026-03-30T08:15:00+00:00",
    "updated_at": "2026-03-30T08:30:00+00:00",
    "client": {
      "id": 1,
      "name": "John Doe"
    },
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Delete Service Order (Admin Only)

**DELETE** `/api/admin/service-orders/{id}`

Delete a service order. Requires `admin` role.

**Success Response (200 OK):**

```json
{
  "message": "Service order deleted successfully"
}
```

---

## Error Responses

### Validation Error (422 Unprocessable Entity)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "service_type": ["The service type field is required."],
    "title": ["The title field is required."],
    "budget_max": ["The budget max must be greater than or equal to budget min."]
  }
}
```

### Unauthorized (401 Unauthorized)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403 Forbidden)

```json
{
  "message": "User does not have the necessary permissions."
}
```

### Not Found (404 Not Found)

```json
{
  "message": "Service order not found."
}
```

---

## Status Values

| Status | Description |
|--------|-------------|
| `pending` | Initial status when submitted |
| `reviewing` | Admin is reviewing the proposal |
| `approved` | Admin approved the service order |
| `rejected` | Admin rejected the service order |

---

## Notifications

When a new service order is created:
- All users with `admin` role receive an in-app notification
- All admins receive an email notification
- Notification type: `service_order_created`
- Notification data includes: `service_order_id`, `service_type`, `client_name`, `budget_min`, `budget_max`

---

## Frontend Integration Example (Vue 3)

```vue
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const form = ref({
  service_type: '',
  title: '',
  description: '',
  budget_min: null,
  budget_max: null,
  deadline: null
})

const submitServiceOrder = async () => {
  try {
    const response = await axios.post('/api/service-orders', form.value)
    alert(response.data.message)
  } catch (error) {
    if (error.response?.status === 422) {
      // Handle validation errors
      console.error(error.response.data.errors)
    }
  }
}
</script>

<template>
  <form @submit.prevent="submitServiceOrder">
    <input v-model="form.service_type" placeholder="Service Type" required />
    <input v-model="form.title" placeholder="Title" required />
    <textarea v-model="form.description" placeholder="Description" required></textarea>
    <input v-model.number="form.budget_min" type="number" placeholder="Min Budget" />
    <input v-model.number="form.budget_max" type="number" placeholder="Max Budget" />
    <input v-model="form.deadline" type="date" />
    <button type="submit">Submit Proposal</button>
  </form>
</template>
```

---

## Admin Dashboard Example (Vue 3)

```vue
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const serviceOrders = ref([])

const fetchServiceOrders = async () => {
  const response = await axios.get('/api/admin/service-orders')
  serviceOrders.value = response.data.service_orders
}

const updateStatus = async (id, status) => {
  await axios.post(`/api/admin/service-orders/${id}/status`, { status })
  fetchServiceOrders()
}

onMounted(() => {
  fetchServiceOrders()
})
</script>

<template>
  <div>
    <h1>Service Orders</h1>
    <div v-for="order in serviceOrders" :key="order.id">
      <h3>{{ order.title }}</h3>
      <p>Type: {{ order.service_type }}</p>
      <p>Client: {{ order.client.name }}</p>
      <p>Budget: ${{ order.budget_min }} - ${{ order.budget_max }}</p>
      <p>Status: {{ order.status }}</p>
      <select @change="updateStatus(order.id, $event.target.value)">
        <option value="pending">Pending</option>
        <option value="reviewing">Reviewing</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>
  </div>
</template>
```
