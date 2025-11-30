# Tyazubwenge Management System

A comprehensive Stock & Sales Management System designed for Tyazubwenge Ltd, specializing in laboratory products measured in kilograms, grams, and milligrams.

## Features

### Core Features
- **Stock & Inventory Management**
  - Multi-unit support (kg, g, mg) with automatic conversion
  - Real-time inventory tracking
  - Batch/lot and expiration date tracking
  - Low stock alerts
  - Near-expiry product notifications
  - Add quantities to existing products
  - Edit product information

- **Sales & Billing**
  - Point of Sale (POS) system
  - Barcode/QR code scanning support
  - Digital invoice generation
  - Support for all printer sizes (58mm thermal, 80mm thermal, A4)
  - Retail and wholesale pricing
  - Tax and discount handling
  - Multiple payment methods (cash, mobile money, bank transfer, credit)

- **Customer Management**
  - Customer database with purchase history
  - Loyalty points system
  - Credit sales tracking

- **Supplier Management**
  - Supplier database
  - Purchase order tracking

- **Reports & Analytics**
  - Daily, weekly, monthly sales reports
  - Stock valuation reports
  - Profit & loss analysis
  - Top products report
  - Custom reports (sales by customer, sales by category, slow-moving products)
  - Export to CSV/Excel
  - Pagination support

- **User & Role Management**
  - Admin, Cashier, Stock Manager, Accountant roles
  - Role-based permissions
  - Secure authentication

- **Multi-Branch Support**
  - Multiple branch/warehouse management
  - Branch-based reporting

## Technology Stack

- **Frontend:** HTML5, Bootstrap 5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP (Pure PHP, no framework)
- **Database:** MySQL
- **No .htaccess required**

## Installation

### Prerequisites
- XAMPP/WAMP/LAMP (PHP 7.4+ and MySQL 5.7+)
- Web server (Apache/Nginx)
- Modern web browser

### Setup Steps

1. **Clone/Download the project**
   ```bash
   cd D:\XAMPP\htdocs\tyazubwenge_v2
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema:
     - Go to Import tab
     - Select `database/schema.sql`
     - Click Go

   Or via command line:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php`
   - Update database credentials if needed:
     ```php
     private $host = "localhost";
     private $db_name = "tyazubwenge_db";
     private $username = "root";
     private $password = "";
     ```

4. **Configure Base URL**
   - Edit `config/config.php`
   - Update BASE_URL if needed:
     ```php
     define('BASE_URL', 'http://localhost/tyazubwenge_v2/');
     ```

5. **Access the Application**
   - Open browser: `http://localhost/tyazubwenge_v2/`
   - Default login credentials:
     - Username: `admin`
     - Password: `password`

## Project Structure

```
tyazubwenge_v2/
├── api/                 # API endpoints
│   ├── products.php
│   ├── sales.php
│   ├── stock.php
│   ├── customers.php
│   ├── suppliers.php
│   ├── reports.php
│   ├── search.php
│   └── users.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── main.js
│       ├── products.js
│       ├── pos.js
│       ├── stock.js
│       ├── sales.js
│       ├── customers.js
│       └── reports.js
├── classes/             # PHP classes
│   ├── User.php
│   ├── Product.php
│   ├── Stock.php
│   ├── Sale.php
│   ├── Customer.php
│   ├── Supplier.php
│   └── Report.php
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   └── header.php
├── index.php           # Dashboard
├── login.php
├── logout.php
├── products.php
├── pos.php            # Point of Sale
├── stock.php
├── sales.php
├── customers.php
├── reports.php
├── invoice.php        # Invoice printing
└── users.php
```

## Usage Guide

### Adding Products
1. Navigate to **Products** menu
2. Click **Add Product**
3. Fill in product details (name, SKU, barcode, prices, unit)
4. Set minimum stock level
5. Click **Save Product**

### Processing Sales
1. Go to **POS** menu
2. Search/scan products to add to cart
3. Select customer (optional)
4. Apply discount/tax if needed
5. Choose payment method
6. Click **Complete Sale**
7. Print invoice (supports all printer sizes)

### Managing Stock
1. Navigate to **Stock** menu
2. View current stock levels
3. Click **Add Stock** to add quantities to existing products
4. Filter by low stock or near expiry items

### Generating Reports
1. Go to **Reports** menu
2. Select report type
3. Choose date range
4. View results
5. Export to CSV if needed

### Invoice Printing
- **A4 Size:** Standard printer format
- **58mm Thermal:** Small receipt printers
- **80mm Thermal:** Standard receipt printers
- All sizes are optimized for their respective printer types

## Key Features Implementation

### Multi-Unit Support
The system handles conversions between kg, g, and mg automatically. Products can be sold in one unit while tracked in another.

### Adding Quantities to Existing Products
- Go to Stock Management
- Click "Add Qty" button on any product
- Enter quantity and unit
- Stock is automatically updated

### Edit Functionality
- All entities (products, customers, users) support edit
- Click edit icon on any record
- Modify and save changes

### Pagination
- All list views support pagination
- Configurable items per page
- Page navigation controls

### Custom Reports
- Sales by Customer
- Sales by Category
- Slow Moving Products
- All with date range filtering

## Security Features

- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection (input sanitization)
- Session management
- Role-based access control

## Default Roles & Permissions

- **Admin:** Full access to all features
- **Cashier:** Sales, customers, view products
- **Stock Manager:** Products, stock, suppliers, purchases
- **Accountant:** Reports, financial data, view all

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists

### Session Issues
- Check PHP session configuration
- Ensure cookies are enabled in browser

### Invoice Not Printing
- Check printer settings
- Try different size options (A4, 58mm, 80mm)
- Ensure pop-up blocker is disabled

## Testing

The system includes comprehensive test suites to verify all functionality:

### Run All Tests
Access: `http://localhost/tyazubwenge_v2/run_all_tests.php`

### Individual Test Suites

1. **System Tests** (`test.php`)
   - Database connection tests
   - Class instantiation tests
   - Table existence verification
   - Helper function tests
   - File structure verification

2. **API Tests** 
   - `test_api.php` - Server-side API tests (requires curl)
   - `test_api_browser.html` - Browser-based API tests (recommended)
   - Tests all API endpoints
   - Verifies JSON responses
   - Checks HTTP status codes
   - Validates API structure

3. **Functional Tests** (`test_functionality.php`)
   - Tests CRUD operations
   - Creates sample data
   - Tests business logic
   - Verifies data integrity

### Running Tests

1. **Before First Use:**
   ```bash
   # Access in browser
   http://localhost/tyazubwenge_v2/run_all_tests.php
   ```

2. **Check Results:**
   - Green checkmarks (✓) = Passed
   - Red X marks (✗) = Failed
   - Yellow warnings (⚠) = Warnings

3. **Fix Issues:**
   - Review failed tests
   - Check error messages
   - Verify database setup
   - Check file permissions

### Test Coverage

- ✅ Database connectivity
- ✅ All PHP classes
- ✅ API endpoints
- ✅ CRUD operations
- ✅ Business logic
- ✅ Helper functions
- ✅ File structure

## Support

For issues or questions, please contact the development team.

## License

Proprietary - Tyazubwenge Ltd

---

**Version:** 2.0  
**Last Updated:** 2024

