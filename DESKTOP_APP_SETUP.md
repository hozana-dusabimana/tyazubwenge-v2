# Desktop Application Setup Guide

## Overview
The system has been prepared to support a desktop application that can work offline and sync data when connected to the server.

## What Has Been Added

### 1. API Authentication (`api/auth.php`)
- **POST**: Login and receive API token
- **GET**: Verify token validity
- Tokens expire after 30 days (configurable)

### 2. Sync API (`api/sync.php`)
- **GET**: Check sync status and count of pending updates
- **POST**: Upload/sync data from desktop app in batches
- Supports: sales, stock, customers
- Handles duplicate detection via `local_id`
- Returns sync results with success/failure counts

### 3. Download API (`api/download.php`)
- **GET**: Download all data or specific entities
- Supports incremental sync via `last_sync` parameter
- Returns: products, stock, customers, sales, categories, brands, suppliers

### 4. Database Schema Updates (`database/sync_schema.sql`)
- `sync_mappings` table: Maps local_id to server_id
- `local_id` columns: Added to sales, stock_inventory, customers
- `updated_at` timestamps: Added to all tables for conflict resolution
- `api_tokens` table: For token management (future enhancement)

### 5. Documentation
- `DESKTOP_API_DOCUMENTATION.md`: Complete API reference
- `INSTALL_SYNC.md`: Installation and setup guide
- `DESKTOP_APP_SETUP.md`: This file

## Quick Start

### Step 1: Run Database Migration
```sql
SOURCE database/sync_schema.sql;
```

### Step 2: Test Authentication
```bash
curl -X POST http://localhost/tyazubwenge_v2/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

### Step 3: Test Sync
```bash
# Get token from Step 2, then:
curl "http://localhost/tyazubwenge_v2/api/sync.php?token=YOUR_TOKEN&entity=all"
```

## Desktop App Integration Flow

1. **Login**: POST to `/api/auth.php` → Get token
2. **Initial Download**: GET `/api/download.php?entity=all` → Store locally
3. **Offline Operations**: Generate `local_id` for each operation, queue locally
4. **Sync When Online**: 
   - POST `/api/sync.php` with queued records
   - GET `/api/download.php?last_sync=TIMESTAMP` for updates
   - Update local database with server IDs

## Key Features

✅ **Token-based Authentication**: Secure API access
✅ **Offline Support**: Queue operations when offline
✅ **Batch Sync**: Upload multiple records at once
✅ **Duplicate Prevention**: Uses `local_id` to prevent duplicates
✅ **Incremental Sync**: Only download changed data
✅ **Conflict Resolution**: Uses `updated_at` timestamps
✅ **Error Handling**: Detailed error messages for failed syncs

## API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/auth.php` | POST | Login and get token |
| `/api/auth.php` | GET | Verify token |
| `/api/sync.php` | GET | Check sync status |
| `/api/sync.php` | POST | Upload/sync data |
| `/api/download.php` | GET | Download data |

## Data Flow

```
Desktop App (Offline)
    ↓
Generate local_id (UUID)
    ↓
Store in local queue
    ↓
[Connection Available]
    ↓
POST /api/sync.php (batch upload)
    ↓
Server processes & returns server_id
    ↓
Update local mapping (local_id → server_id)
    ↓
GET /api/download.php (get updates)
    ↓
Merge with local data
```

## Supported Entities

- ✅ Sales
- ✅ Stock Inventory
- ✅ Customers
- ✅ Products (download only)
- ✅ Categories (download only)
- ✅ Brands (download only)
- ✅ Suppliers (download only)

## Security Considerations

⚠️ **Current Implementation**: Tokens stored in sessions (simple)
⚠️ **Production Recommendation**: 
- Store tokens in database (`api_tokens` table)
- Implement token refresh mechanism
- Use HTTPS
- Add rate limiting
- Consider IP whitelisting

## Next Steps for Desktop App

1. Implement local database (SQLite recommended)
2. Create sync queue system
3. Implement connection detection
4. Add sync progress UI
5. Handle conflicts intelligently
6. Implement retry logic for failed syncs
7. Add sync status indicators

## Testing

Test the sync functionality:
```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost/tyazubwenge_v2/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' | jq -r '.token')

# 2. Check sync status
curl "http://localhost/tyazubwenge_v2/api/sync.php?token=$TOKEN&entity=all"

# 3. Download data
curl "http://localhost/tyazubwenge_v2/api/download.php?token=$TOKEN&entity=products"
```

## Support

For detailed API documentation, see `DESKTOP_API_DOCUMENTATION.md`
For installation instructions, see `INSTALL_SYNC.md`

