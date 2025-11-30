# Desktop Application API Documentation

## Overview
This API enables desktop applications to work offline and sync data when connected to the server.

## Authentication

### Login and Get Token
**Endpoint:** `POST /api/auth.php`

**Request:**
```json
{
  "username": "admin",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "token": "abc123...",
  "expires": "2025-12-30 12:00:00",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin",
    "branch_id": 1
  }
}
```

### Verify Token
**Endpoint:** `GET /api/auth.php?token=YOUR_TOKEN`

**Response:**
```json
{
  "success": true,
  "valid": true,
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin",
    "branch_id": 1
  }
}
```

## Sync Operations

### Check Sync Status
**Endpoint:** `GET /api/sync.php?token=YOUR_TOKEN&last_sync=2025-11-29 12:00:00&entity=all`

**Parameters:**
- `token` (required): API authentication token
- `last_sync` (optional): Last sync timestamp
- `entity` (optional): Entity type (all, sales, stock, products, customers)

**Response:**
```json
{
  "success": true,
  "server_time": "2025-11-30 10:00:00",
  "last_sync": "2025-11-29 12:00:00",
  "data": {
    "sales": {
      "count": 5,
      "last_update": "2025-11-30 09:00:00"
    },
    "stock": {
      "count": 10,
      "last_update": "2025-11-30 08:00:00"
    }
  }
}
```

### Upload/Sync Data
**Endpoint:** `POST /api/sync.php`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Request:**
```json
{
  "entity": "sales",
  "records": [
    {
      "local_id": "local_123",
      "invoice_number": "INV-001",
      "customer_id": 1,
      "items": [
        {
          "product_id": 1,
          "quantity": 10,
          "unit_price": 100,
          "total": 1000
        }
      ],
      "subtotal": 1000,
      "discount": 0,
      "tax": 0,
      "final_amount": 1000,
      "payment_method": "cash",
      "created_at": "2025-11-30 10:00:00"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "synced": 1,
  "failed": 0,
  "errors": []
}
```

### Download Data
**Endpoint:** `GET /api/download.php?token=YOUR_TOKEN&entity=all&last_sync=2025-11-29 12:00:00`

**Parameters:**
- `token` (required): API authentication token
- `entity` (optional): Entity type (all, products, stock, customers, sales, categories, brands, suppliers)
- `last_sync` (optional): Only download records updated after this timestamp

**Response:**
```json
{
  "success": true,
  "server_time": "2025-11-30 10:00:00",
  "data": {
    "products": [...],
    "stock": [...],
    "customers": [...],
    "sales": [...]
  }
}
```

## Standard API Endpoints

All standard API endpoints support token authentication via:
- Query parameter: `?token=YOUR_TOKEN`
- Header: `Authorization: Bearer YOUR_TOKEN`

### Available Endpoints:
- `/api/products.php` - Products CRUD
- `/api/stock.php` - Stock management
- `/api/sales.php` - Sales operations
- `/api/customers.php` - Customers CRUD
- `/api/suppliers.php` - Suppliers CRUD
- `/api/categories.php` - Categories CRUD
- `/api/brands.php` - Brands CRUD
- `/api/reports.php` - Reports generation

## Offline Sync Workflow

### 1. Initial Setup
1. User logs in via desktop app
2. Desktop app receives API token
3. Desktop app downloads all data: `GET /api/download.php?entity=all`
4. Store data locally (SQLite, JSON, etc.)

### 2. Offline Operations
1. User performs operations (sales, stock updates, etc.)
2. Each operation gets a `local_id` (UUID or timestamp-based)
3. Store operations in local queue with status: `pending_sync`

### 3. Online Sync
1. Desktop app checks connection
2. Verify token: `GET /api/auth.php?token=YOUR_TOKEN`
3. Check sync status: `GET /api/sync.php?last_sync=LAST_SYNC_TIME`
4. Upload pending operations: `POST /api/sync.php`
5. Download updates: `GET /api/download.php?last_sync=LAST_SYNC_TIME`
6. Update local database with server IDs from sync_mappings

### 4. Conflict Resolution
- Use `updated_at` timestamps
- Server timestamp takes precedence
- Desktop app should merge changes intelligently

## Database Schema Updates

Run the migration file to add sync support:
```sql
SOURCE database/sync_schema.sql;
```

This adds:
- `sync_mappings` table for local_id to server_id mapping
- `local_id` columns to sales, stock_inventory, customers
- `updated_at` timestamps to all tables
- `api_tokens` table for token management

## Best Practices

1. **Token Management:**
   - Store token securely on desktop
   - Refresh token before expiry
   - Handle token expiration gracefully

2. **Data Sync:**
   - Sync in batches (max 100 records per request)
   - Use incremental sync (last_sync parameter)
   - Handle network errors with retry logic

3. **Offline Queue:**
   - Queue all operations when offline
   - Mark operations as synced after successful upload
   - Keep failed syncs for retry

4. **Error Handling:**
   - Log all sync errors
   - Provide user feedback on sync status
   - Allow manual sync retry

5. **Data Integrity:**
   - Validate data before sync
   - Use transactions for batch operations
   - Handle duplicate detection (local_id)

## Example Desktop App Flow

```javascript
// 1. Login
const authResponse = await fetch('/api/auth.php', {
  method: 'POST',
  body: JSON.stringify({ username, password })
});
const { token } = await authResponse.json();

// 2. Download initial data
const downloadResponse = await fetch(`/api/download.php?token=${token}&entity=all`);
const { data } = await downloadResponse.json();
// Store in local database

// 3. When offline, queue operations
const pendingSale = {
  local_id: generateUUID(),
  invoice_number: 'INV-001',
  // ... sale data
};
localDB.queueOperation('sales', pendingSale);

// 4. When online, sync
const pendingOps = localDB.getPendingOperations();
for (const entity of ['sales', 'stock', 'customers']) {
  const records = pendingOps[entity];
  if (records.length > 0) {
    await fetch('/api/sync.php', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        entity: entity,
        records: records
      })
    });
    // Mark as synced
    localDB.markSynced(entity, records);
  }
}
```

