# Quick Test Guide

## 🚀 Quick Start Testing

### Step 1: Run Master Test Suite
Open in browser: `http://localhost/tyazubwenge_v2/run_all_tests.php`

This will run all test suites in one page.

### Step 2: Individual Tests

#### System Tests
```
http://localhost/tyazubwenge_v2/test.php
```
**What it tests:**
- ✅ Database connection
- ✅ All tables exist
- ✅ All classes work
- ✅ Helper functions
- ✅ File structure

**Expected:** All green checkmarks

#### API Tests (Browser)
```
http://localhost/tyazubwenge_v2/test_api_browser.html
```
**Note:** You must be logged in first!

**What it tests:**
- ✅ All API endpoints respond
- ✅ JSON format is correct
- ✅ HTTP status codes

**Expected:** All endpoints return success

#### Functional Tests
```
http://localhost/tyazubwenge_v2/test_functionality.php
```
**What it tests:**
- ✅ Create products
- ✅ Add stock
- ✅ Create customers
- ✅ Process sales
- ✅ Generate reports

**Expected:** All operations succeed

## ✅ Test Checklist

Before using the system, verify:

- [ ] Database connection works
- [ ] All tables exist
- [ ] Can login with admin/password
- [ ] Dashboard loads
- [ ] Can create a product
- [ ] Can add stock
- [ ] Can create a customer
- [ ] Can process a sale
- [ ] Invoice prints correctly
- [ ] Reports generate

## 🔧 Common Issues

### "Database connection failed"
- Check `config/database.php` credentials
- Ensure MySQL is running
- Verify database exists

### "Table doesn't exist"
- Import `database/schema.sql`
- Check database name is correct

### "API tests fail"
- Make sure you're logged in
- Check session is working
- Verify API files exist

### "Functional tests fail"
- Database might be empty
- Check permissions
- Review PHP error logs

## 📊 Test Results Interpretation

- **Green ✓** = Test passed
- **Red ✗** = Test failed (fix required)
- **Yellow ⚠** = Warning (review but may work)

## 🎯 Success Criteria

System is ready when:
1. ✅ All system tests pass
2. ✅ All API tests return success
3. ✅ Functional tests create data successfully
4. ✅ No PHP errors in logs
5. ✅ Can login and navigate

---

**Need Help?** Review the full README.md for detailed information.
