# Invoice API Documentation

## Overview

The Invoice API provides CRUD operations for managing invoices. Only **admin** users can create, update, and delete invoices. Any authenticated user can list and view invoice details.

---

## Authentication

All endpoints require authentication using a Bearer token obtained from the login endpoint.

### Login Endpoint
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@xenon.com",
  "password": "password"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@xenon.com",
    "role": "admin"
  },
  "token": "1|abc123..."
}
```

Use the returned `token` in the `Authorization` header for subsequent requests:
```
Authorization: Bearer 1|abc123...
```

---

## Endpoints

### 1. List Invoices (Any Authenticated User)

Retrieve a list of invoices with optional filtering by `project_id` and/or `client_id`.

```http
GET /api/invoices
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `project_id` | integer | No | Filter by project ID |
| `client_id` | integer | No | Filter by client ID |

**Examples:**

- Get all invoices: `GET /api/invoices`
- Filter by project: `GET /api/invoices?project_id=1`
- Filter by client: `GET /api/invoices?client_id=1`
- Filter by both: `GET /api/invoices?project_id=1&client_id=1`

**Success Response (200):**
```json
{
  "invoices": [
    {
      "invoice_id": 1,
      "project_id": 1,
      "client_id": 1,
      "issued_by": 1,
      "updated_by": null,
      "date_issued": "2026-03-24T00:00:00.000000Z",
      "due_date": "2026-04-24T00:00:00.000000Z",
      "amount": "15000.00",
      "status": "pending",
      "file_path": null,
      "created_at": "2026-03-23T19:07:58.000000Z",
      "updated_at": "2026-03-23T19:07:58.000000Z"
    }
  ]
}
```

**Error Responses:**

- **401 Unauthorized** - Invalid or missing token
```json
{
  "message": "Unauthorized"
}
```

---

### 2. Create Invoice (Admin Only)

Create a new invoice. Only users with `admin` role can access this endpoint.

```http
POST /api/admin/invoices
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Payload:**
```json
{
  "project_id": 1,
  "client_id": 1,
  "date_issued": "2026-03-24",
  "due_date": "2026-04-24",
  "amount": 15000.00,
  "status": "pending",
  "file_path": "/invoices/2026/invoice-001.pdf"
}
```

**Field Descriptions:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `project_id` | integer | Yes | ID of the project this invoice is for |
| `client_id` | integer | Yes | ID of the client being invoiced |
| `date_issued` | date (YYYY-MM-DD) | Yes | Date the invoice was issued |
| `due_date` | date (YYYY-MM-DD) | Yes | Payment due date |
| `amount` | decimal | Yes | Invoice amount (must be >= 0) |
| `status` | string | No | Invoice status: `pending`, `paid`, `overdue` (default: `pending`) |
| `file_path` | string | No | Path to invoice file (optional) |

**Success Response (201):**
```json
{
  "message": "Invoice created successfully",
  "invoice": {
    "invoice_id": 1,
    "project_id": 1,
    "client_id": 1,
    "issued_by": 1,
    "updated_by": null,
    "date_issued": "2026-03-24T00:00:00.000000Z",
    "due_date": "2026-04-24T00:00:00.000000Z",
    "amount": "15000.00",
    "status": "pending",
    "file_path": null,
    "created_at": "2026-03-23T19:07:58.000000Z",
    "updated_at": "2026-03-23T19:07:58.000000Z"
  }
}
```

**Error Responses:**

- **401 Unauthorized** - Invalid or missing token
```json
{
  "message": "Unauthorized"
}
```

- **403 Forbidden** - User is not an admin
```json
{
  "message": "Forbidden. Insufficient permissions."
}
```

- **422 Validation Error** - Invalid payload
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["The amount field must be a number."],
    "due_date": ["The due date field must be a valid date."]
  }
}
```

---

### 3. Get Invoice Details (Any Authenticated User)

Retrieve details of a specific invoice by ID.

```http
GET /api/invoices/{id}
Authorization: Bearer {token}
```

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Invoice ID |

**Success Response (200):**
```json
{
  "invoice": {
    "invoice_id": 1,
    "project_id": 1,
    "client_id": 1,
    "issued_by": 1,
    "updated_by": null,
    "date_issued": "2026-03-24T00:00:00.000000Z",
    "due_date": "2026-04-24T00:00:00.000000Z",
    "amount": "15000.00",
    "status": "pending",
    "file_path": null,
    "created_at": "2026-03-23T19:07:58.000000Z",
    "updated_at": "2026-03-23T19:07:58.000000Z"
  }
}
```

**Error Responses:**

- **401 Unauthorized** - Invalid or missing token
- **404 Not Found** - Invoice not found
```json
{
  "message": "Invoice not found"
}
```

---

### 4. Update Invoice (Admin Only)

Update an existing invoice. Only users with `admin` role can access this endpoint.

```http
PUT /api/admin/invoices/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Invoice ID |

**Request Payload (all fields optional):**
```json
{
  "date_issued": "2026-03-25",
  "due_date": "2026-05-01",
  "amount": 17500.00,
  "status": "paid",
  "file_path": "/invoices/2026/invoice-001-updated.pdf"
}
```

**Success Response (200):**
```json
{
  "message": "Invoice updated successfully",
  "invoice": {
    "invoice_id": 1,
    "project_id": 1,
    "client_id": 1,
    "issued_by": 1,
    "updated_by": 1,
    "date_issued": "2026-03-25T00:00:00.000000Z",
    "due_date": "2026-05-01T00:00:00.000000Z",
    "amount": "17500.00",
    "status": "paid",
    "file_path": "/invoices/2026/invoice-001-updated.pdf",
    "created_at": "2026-03-23T19:07:58.000000Z",
    "updated_at": "2026-03-23T19:15:00.000000Z"
  }
}
```

**Error Responses:**

- **401 Unauthorized** - Invalid or missing token
- **403 Forbidden** - User is not an admin
- **404 Not Found** - Invoice not found
- **422 Validation Error** - Invalid payload

---

### 5. Delete Invoice (Admin Only)

Delete an invoice. Only users with `admin` role can access this endpoint.

```http
DELETE /api/admin/invoices/{id}
Authorization: Bearer {token}
```

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Invoice ID |

**Success Response (200):**
```json
{
  "message": "Invoice deleted successfully"
}
```

**Error Responses:**

- **401 Unauthorized** - Invalid or missing token
- **403 Forbidden** - User is not an admin
- **404 Not Found** - Invoice not found

---

## Frontend Integration Examples

### JavaScript / Axios

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Set auth token after login
const setAuthToken = (token) => {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

// Login
const login = async (email, password) => {
  const response = await api.post('/auth/login', { email, password });
  setAuthToken(response.data.token);
  return response.data;
};

// Create invoice (admin only)
const createInvoice = async (invoiceData) => {
  const response = await api.post('/admin/invoices', invoiceData);
  return response.data;
};

// List invoices (with optional filters)
const listInvoices = async (filters = {}) => {
  const response = await api.get('/invoices', { params: filters });
  return response.data;
};

// Get invoice details
const getInvoice = async (invoiceId) => {
  const response = await api.get(`/invoices/${invoiceId}`);
  return response.data;
};

// Update invoice (admin only)
const updateInvoice = async (invoiceId, invoiceData) => {
  const response = await api.put(`/admin/invoices/${invoiceId}`, invoiceData);
  return response.data;
};

// Delete invoice (admin only)
const deleteInvoice = async (invoiceId) => {
  const response = await api.delete(`/admin/invoices/${invoiceId}`);
  return response.data;
};
```

### React Hook Example

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

const useInvoice = (invoiceId, token) => {
  const [invoice, setInvoice] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchInvoice = async () => {
      try {
        const response = await axios.get(`http://localhost:8000/api/invoices/${invoiceId}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        setInvoice(response.data.invoice);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchInvoice();
  }, [invoiceId, token]);

  return { invoice, loading, error };
};
```

---

## Status Values

| Status | Description |
|--------|-------------|
| `pending` | Invoice created, awaiting payment |
| `paid` | Invoice has been paid |
| `overdue` | Payment deadline has passed |

---

## Notes

1. **Role-based Access Control:**
   - `POST /api/admin/invoices` - Admin only
   - `PUT /api/admin/invoices/{id}` - Admin only
   - `DELETE /api/admin/invoices/{id}` - Admin only
   - `GET /api/invoices` - Any authenticated user (list with filters)
   - `GET /api/invoices/{id}` - Any authenticated user (single invoice)

2. **Date Format:** All dates should be in `YYYY-MM-DD` format (e.g., `2026-03-24`)

3. **Amount:** Must be a positive number with up to 2 decimal places

4. **Auto-populated Fields:**
   - `issued_by` - Automatically set to the authenticated admin user's ID on creation
   - `updated_by` - Automatically set to the authenticated admin user's ID on update
   - `created_at` / `updated_at` - Automatically managed by the system
