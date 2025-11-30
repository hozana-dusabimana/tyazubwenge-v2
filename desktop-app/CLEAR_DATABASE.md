# How to Delete All Data from Local SQLite Database

There are several ways to clear data from the local SQLite database used by the desktop application.

## Method 1: Using the Application (Recommended)

The application now includes built-in functions to clear data. You can use them from the browser console:

1. Open the desktop application
2. Press `F12` or `Ctrl+Shift+I` to open Developer Tools
3. Go to the Console tab
4. Run one of these commands:

### Clear All Data (except settings)
```javascript
await window.electronAPI.db.clearAll()
```

### Clear Only Sync Data (pending syncs)
```javascript
await window.electronAPI.db.clearSync()
```

### Clear Only Sales Data
```javascript
await window.electronAPI.db.clearSales()
```

### Clear Only Stock Data
```javascript
await window.electronAPI.db.clearStock()
```

## Method 2: Delete the Database File

You can manually delete the database file. The database is located at:

- **Windows**: `%APPDATA%\tyazubwenge-desktop\tyazubwenge.db`
  - Full path example: `C:\Users\YourUsername\AppData\Roaming\tyazubwenge-desktop\tyazubwenge.db`
  
- **macOS**: `~/Library/Application Support/tyazubwenge-desktop/tyazubwenge.db`
  - Full path example: `/Users/YourUsername/Library/Application Support/tyazubwenge-desktop/tyazubwenge.db`
  
- **Linux**: `~/.config/tyazubwenge-desktop/tyazubwenge.db`
  - Full path example: `/home/YourUsername/.config/tyazubwenge-desktop/tyazubwenge.db`

**Steps:**
1. Close the desktop application completely
2. Navigate to the folder above
3. Delete the `tyazubwenge.db` file
4. Restart the application (it will create a new empty database)

## Method 3: Using SQLite Command Line Tool

If you have SQLite installed, you can use the command line:

1. Find the database file location (see Method 2)
2. Open a terminal/command prompt
3. Run:

```bash
sqlite3 "path/to/tyazubwenge.db"
```

Then in SQLite prompt:
```sql
-- Clear all data (except settings)
DELETE FROM sale_items;
DELETE FROM sales;
DELETE FROM stock_inventory;
DELETE FROM customers;
DELETE FROM products;
DELETE FROM categories;
DELETE FROM brands;
DELETE FROM suppliers;
DELETE FROM users;
DELETE FROM sync_queue;
DELETE FROM sync_mappings;

-- Reset auto-increment counters
DELETE FROM sqlite_sequence WHERE name IN ('sales', 'sale_items', 'stock_inventory', 'customers', 'sync_queue', 'sync_mappings');

-- Exit
.quit
```

## Method 4: Using a Database Browser

You can use a SQLite database browser tool like:
- **DB Browser for SQLite** (https://sqlitebrowser.org/)
- **SQLiteStudio** (https://sqlitestudio.pl/)

1. Open the database file with the tool
2. Navigate to each table
3. Right-click and select "Delete All Rows" or run DELETE statements

## Important Notes

⚠️ **Warning**: 
- Clearing data will permanently delete all local records
- Settings (auth token, API URL) are preserved when using `clearAll()`
- If you delete the entire database file, you'll need to login again
- Make sure you've synced important data to the server before clearing

## What Gets Deleted

### `clearAll()` - Deletes:
- ✅ All sales and sale items
- ✅ All stock inventory
- ✅ All customers
- ✅ All products, categories, brands, suppliers
- ✅ All users
- ✅ All sync mappings and queue
- ❌ Settings (auth token, API URL) - **PRESERVED**

### `clearSync()` - Deletes:
- ✅ Only pending sync records
- ✅ Sync queue
- ✅ Sync mappings
- ❌ Synced data - **PRESERVED**

### `clearSales()` - Deletes:
- ✅ All sales and sale items
- ❌ Everything else - **PRESERVED**

### `clearStock()` - Deletes:
- ✅ All stock inventory
- ❌ Everything else - **PRESERVED**


