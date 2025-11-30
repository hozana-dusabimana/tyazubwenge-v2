# Installing Offline Sync Support

## Overview
This guide will help you set up the offline sync capabilities for the desktop application.

## Step 1: Run Database Migration

Run the sync schema migration to add necessary tables and columns:

```sql
SOURCE database/sync_schema.sql;
```

Or manually execute the SQL file in your MySQL client.

This will:
- Create `sync_mappings` table for tracking local_id to server_id mappings
- Add `local_id` columns to sales, stock_inventory, and customers tables
- Add `updated_at` timestamps to all relevant tables
- Create `api_tokens` table for token management

## Step 2: Verify API Endpoints

Test the authentication endpoint:
```bash
curl -X POST http://localhost/tyazubwenge_v2/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

You should receive a JSON response with a token.

## Step 3: Test Sync Endpoints

### Check Sync Status
```bash
curl "http://localhost/tyazubwenge_v2/api/sync.php?token=YOUR_TOKEN&entity=all"
```

### Download Data
```bash
curl "http://localhost/tyazubwenge_v2/api/download.php?token=YOUR_TOKEN&entity=all"
```

## Step 4: Desktop Application Integration

1. **Login Flow:**
   - POST to `/api/auth.php` with username/password
   - Store the received token securely
   - Token expires in 30 days (configurable)

2. **Initial Data Download:**
   - GET `/api/download.php?token=YOUR_TOKEN&entity=all`
   - Store all data locally (SQLite recommended)

3. **Offline Operations:**
   - Generate `local_id` (UUID) for each operation
   - Store operations in local queue
   - Mark as `pending_sync`

4. **Sync When Online:**
   - Check connection
   - Verify token: GET `/api/auth.php?token=YOUR_TOKEN`
   - Upload pending: POST `/api/sync.php` with batch of records
   - Download updates: GET `/api/download.php?last_sync=TIMESTAMP`

## API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/auth.php` | POST | Login and get token |
| `/api/auth.php` | GET | Verify token |
| `/api/sync.php` | GET | Check sync status |
| `/api/sync.php` | POST | Upload/sync data |
| `/api/download.php` | GET | Download data |

## Security Notes

- Tokens are currently stored in sessions (simple implementation)
- For production, implement database-stored tokens with proper expiration
- Use HTTPS in production
- Implement rate limiting for API endpoints
- Consider IP whitelisting for desktop apps

## Troubleshooting

### Token Invalid/Expired
- Re-authenticate via `/api/auth.php`
- Check token expiration time

### Sync Fails
- Check database connection
- Verify all required fields are present
- Check error messages in response

### Duplicate Records
- The system uses `local_id` to prevent duplicates
- If duplicate detected, it skips the record

## Next Steps

1. Implement token refresh mechanism
2. Add batch size limits (recommended: 100 records per batch)
3. Implement conflict resolution strategy
4. Add sync progress tracking
5. Implement retry logic for failed syncs

