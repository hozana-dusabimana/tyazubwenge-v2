# Installation Guide - Tyazubwenge Management System

## Quick Start

### Step 1: Database Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on "Import" tab
3. Select file: `database/schema.sql`
4. Click "Go" to import

**OR** via command line:
```bash
mysql -u root -p < database/schema.sql
```

### Step 2: Configure Database

Edit `config/database.php`:
```php
private $host = "localhost";
private $db_name = "tyazubwenge_db";
private $username = "root";      // Your MySQL username
private $password = "";           // Your MySQL password
```

### Step 3: Configure Base URL

Edit `config/config.php`:
```php
define('BASE_URL', 'http://localhost/tyazubwenge_v2/');
```
Change if your installation path is different.

### Step 4: Access the System

1. Open browser: `http://localhost/tyazubwenge_v2/`
2. Login with:
   - **Username:** `admin`
   - **Password:** `password`

### Step 5: Change Default Password

**IMPORTANT:** After first login, change the admin password:
1. Go to Users menu (Admin only)
2. Edit admin user
3. Set new password

## Verification Checklist

- [ ] Database imported successfully
- [ ] Can access login page
- [ ] Can login with default credentials
- [ ] Dashboard loads correctly
- [ ] Can navigate to all menus
- [ ] Changed default password

## Step 6: Run Tests (Recommended)

After installation, run the test suite to verify everything works:

1. **Run Master Test Suite:**
   ```
   http://localhost/tyazubwenge_v2/run_all_tests.php
   ```

2. **Or run individual tests:**
   - System Tests: `test.php`
   - API Tests: `test_api_browser.html` (login first)
   - Functional Tests: `test_functionality.php`

3. **Verify Results:**
   - All tests should show green checkmarks (✓)
   - If any tests fail, review the error messages
   - Fix issues before using the system

See `QUICK_TEST.md` for detailed testing instructions.

## Troubleshooting

### Cannot connect to database
- Check MySQL service is running
- Verify credentials in `config/database.php`
- Ensure database `tyazubwenge_db` exists

### Page not found errors
- Verify BASE_URL in `config/config.php` matches your installation path
- Check Apache/Nginx is running
- Ensure files are in correct directory

### Session errors
- Check PHP session directory is writable
- Verify `session_start()` is called in `config/config.php`

## Next Steps

1. Add your first product
2. Add stock quantities
3. Create customers
4. Process a test sale
5. Generate reports

For detailed usage, see README.md

