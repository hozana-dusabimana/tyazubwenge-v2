# Complete Testing Guide - Tyazubwenge Management System

## Overview

The system includes comprehensive test suites to ensure all functionality works correctly before deployment.

## Test Suites Available

### 1. Master Test Runner
**File:** `run_all_tests.php`  
**Access:** `http://localhost/tyazubwenge_v2/run_all_tests.php`

**Description:**  
Runs all test suites in one page with iframes. Best for getting a complete overview.

**What it does:**
- Displays all three test suites simultaneously
- Provides quick overview of system health
- Easy to see all results at once

---

### 2. System Tests
**File:** `test.php`  
**Access:** `http://localhost/tyazubwenge_v2/test.php`

**Description:**  
Comprehensive system-level tests that verify infrastructure and setup.

**Tests Included:**

#### Database Tests
- ✅ Database connection
- ✅ Database selection
- ✅ Users table exists
- ✅ Products table exists
- ✅ Sales table exists
- ✅ Stock inventory table exists

#### Class Tests
- ✅ User class instantiation
- ✅ Product class instantiation
- ✅ Stock class instantiation
- ✅ Sale class instantiation
- ✅ Customer class instantiation
- ✅ Supplier class instantiation
- ✅ Report class instantiation

#### User Management Tests
- ✅ Get all users
- ✅ Get user count
- ✅ Admin user exists
- ✅ User login function

#### Product Management Tests
- ✅ Get all products
- ✅ Get product count
- ✅ Product search function

#### Stock Management Tests
- ✅ Get all stock
- ✅ Get low stock items
- ✅ Get near expiry items

#### Customer Management Tests
- ✅ Get all customers
- ✅ Get customer count

#### Sales Management Tests
- ✅ Get all sales
- ✅ Get sales count
- ✅ Get sales summary

#### Report Tests
- ✅ Sales report generation
- ✅ Top products report
- ✅ Stock valuation report
- ✅ Profit loss report

#### Helper Function Tests
- ✅ Format currency function
- ✅ Generate invoice number
- ✅ Generate PO number
- ✅ Unit conversion function

#### File Structure Tests
- ✅ Config files exist
- ✅ Class files exist
- ✅ API files exist
- ✅ Frontend pages exist
- ✅ Assets directory exists

**Expected Result:** All tests should pass (green checkmarks)

---

### 3. API Endpoint Tests
**Files:** 
- `test_api.php` (Server-side, requires curl)
- `test_api_browser.html` (Browser-based, recommended)

**Access:** 
- Server: `http://localhost/tyazubwenge_v2/test_api.php`
- Browser: `http://localhost/tyazubwenge_v2/test_api_browser.html`

**Note:** Browser version requires you to be logged in first!

**Description:**  
Tests all REST API endpoints to ensure they return proper JSON responses.

**Tests Included:**
- ✅ GET Products API
- ✅ GET Product by ID
- ✅ GET Stock API
- ✅ GET Low Stock API
- ✅ GET Near Expiry API
- ✅ GET Customers API
- ✅ GET Customer by ID
- ✅ GET Sales API
- ✅ GET Sales Report API
- ✅ GET Top Products API
- ✅ GET Stock Valuation API
- ✅ GET Profit Loss API
- ✅ GET Custom Reports API
- ✅ GET Search API
- ✅ GET Categories API
- ✅ GET Brands API
- ✅ GET Suppliers API
- ✅ GET Users API (Admin only)

**Expected Result:** All endpoints return HTTP 200 with valid JSON

---

### 4. Functional Tests
**File:** `test_functionality.php`  
**Access:** `http://localhost/tyazubwenge_v2/test_functionality.php`

**Description:**  
Tests actual business functionality by performing real operations with sample data.

**Tests Included:**

#### Product Functionality
- ✅ Create test product
- ✅ Update product

#### Stock Functionality
- ✅ Add stock to product
- ✅ Get stock for product

#### Customer Functionality
- ✅ Create test customer

#### Sales Functionality
- ✅ Create test sale (with stock check)

#### Report Functionality
- ✅ Generate sales report
- ✅ Generate top products report

**Expected Result:** All operations complete successfully

**Note:** These tests create actual data in your database. You may want to clean up test data afterward.

---

## How to Run Tests

### Method 1: Master Test Runner (Recommended)
1. Open browser
2. Navigate to: `http://localhost/tyazubwenge_v2/run_all_tests.php`
3. Review all test results in one page

### Method 2: Individual Tests
1. Run each test suite separately
2. Review results for each
3. Fix any issues before proceeding

### Method 3: Command Line (if available)
```bash
# System tests
php test.php > test_results.html

# Functional tests
php test_functionality.php > functional_results.html
```

---

## Interpreting Results

### ✅ Pass (Green)
- Test completed successfully
- Functionality is working correctly
- No action needed

### ❌ Fail (Red)
- Test failed
- Functionality has issues
- **Action Required:** Review error message and fix issue

### ⚠️ Warning (Yellow)
- Test completed but with warnings
- May work but needs review
- **Action:** Review warning message

---

## Common Test Failures & Solutions

### Database Connection Failed
**Error:** "Database Connection: Failed"  
**Solution:**
1. Check MySQL service is running
2. Verify credentials in `config/database.php`
3. Ensure database `tyazubwenge_db` exists

### Table Doesn't Exist
**Error:** "Users Table Exists: Failed"  
**Solution:**
1. Import `database/schema.sql`
2. Verify database name is correct
3. Check for SQL errors during import

### API Tests Fail
**Error:** "API endpoint returned error"  
**Solution:**
1. Ensure you're logged in (for browser tests)
2. Check session is working
3. Verify API files exist in `api/` directory
4. Check PHP error logs

### Class Instantiation Fails
**Error:** "Product Class Instantiation: Failed"  
**Solution:**
1. Verify class files exist in `classes/` directory
2. Check for PHP syntax errors
3. Ensure `config/database.php` is correct

### Functional Tests Fail
**Error:** "Create Test Product: Failed"  
**Solution:**
1. Check database permissions
2. Verify required fields are provided
3. Review PHP error logs
4. Check for constraint violations

---

## Test Data

### Functional Tests Create:
- Test products (with "Test Product" in name)
- Test customers (with "Test Customer" in name)
- Test sales (with "Test sale" in notes)

### Cleaning Test Data
After running functional tests, you may want to clean up:

```sql
-- Delete test products
DELETE FROM products WHERE name LIKE 'Test Product%';

-- Delete test customers
DELETE FROM customers WHERE name LIKE 'Test Customer%';

-- Delete test sales
DELETE FROM sales WHERE notes LIKE '%Test sale%';
```

---

## Best Practices

1. **Run tests after installation** - Verify everything works
2. **Run tests after updates** - Ensure nothing broke
3. **Run tests before deployment** - Final verification
4. **Fix all failures** - Don't deploy with failing tests
5. **Review warnings** - Address important warnings
6. **Keep test results** - Document for troubleshooting

---

## Test Coverage

The test suites cover:

- ✅ Database connectivity
- ✅ All PHP classes
- ✅ All API endpoints
- ✅ CRUD operations
- ✅ Business logic
- ✅ Helper functions
- ✅ File structure
- ✅ Authentication
- ✅ Data integrity

---

## Automated Testing

For continuous testing, you can:

1. **Schedule tests** - Run daily/weekly
2. **Monitor results** - Set up alerts for failures
3. **Track trends** - Keep history of test results
4. **Integration** - Integrate with CI/CD if needed

---

## Support

If tests fail and you can't resolve:
1. Review error messages carefully
2. Check PHP error logs
3. Verify database state
4. Review installation steps
5. Contact support with test results

---

**Last Updated:** 2024  
**Version:** 2.0

