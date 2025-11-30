<?php
/**
 * Tyazubwenge Management System - Comprehensive Test Suite
 * Run this file to test all system components
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'classes/User.php';
require_once 'classes/Product.php';
require_once 'classes/Stock.php';
require_once 'classes/Sale.php';
require_once 'classes/Customer.php';
require_once 'classes/Supplier.php';
require_once 'classes/Report.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>System Test - Tyazubwenge Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .test-item { padding: 10px; margin: 5px 0; border-left: 4px solid #ccc; background: white; }
        .test-item.pass { border-left-color: #28a745; }
        .test-item.fail { border-left-color: #dc3545; }
        .test-item.warning { border-left-color: #ffc107; }
        .pass::before { content: '✓ '; color: #28a745; font-weight: bold; }
        .fail::before { content: '✗ '; color: #dc3545; font-weight: bold; }
        .warning::before { content: '⚠ '; color: #ffc107; font-weight: bold; }
        .summary { margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px; }
        .summary h2 { margin-top: 0; }
        .stats { display: flex; gap: 20px; margin-top: 10px; }
        .stat-box { flex: 1; padding: 15px; background: white; border-radius: 5px; text-align: center; }
        .stat-box h3 { margin: 0; font-size: 2em; }
        .stat-box.success { color: #28a745; }
        .stat-box.failure { color: #dc3545; }
        .stat-box.warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class='test-container'>
        <h1>🧪 Tyazubwenge Management System - Test Suite</h1>";

$tests = [];
$passed = 0;
$failed = 0;
$warnings = 0;

function test($name, $callback) {
    global $tests, $passed, $failed, $warnings;
    try {
        $result = $callback();
        if ($result === true) {
            $tests[] = ['name' => $name, 'status' => 'pass', 'message' => 'OK'];
            $passed++;
        } else if ($result === false) {
            $tests[] = ['name' => $name, 'status' => 'fail', 'message' => 'Failed'];
            $failed++;
        } else {
            $tests[] = ['name' => $name, 'status' => 'warning', 'message' => $result];
            $warnings++;
        }
    } catch (Exception $e) {
        $tests[] = ['name' => $name, 'status' => 'fail', 'message' => $e->getMessage()];
        $failed++;
    }
}

// ========== DATABASE TESTS ==========
echo "<div class='test-section'><h2>1. Database Connection Tests</h2>";

test("Database Connection", function() {
    $db = new Database();
    $conn = $db->getConnection();
    return $conn !== null;
});

test("Database Selection", function() {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT DATABASE()");
    $result = $stmt->fetch();
    return !empty($result);
});

test("Users Table Exists", function() {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    return $stmt->rowCount() > 0;
});

test("Products Table Exists", function() {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SHOW TABLES LIKE 'products'");
    return $stmt->rowCount() > 0;
});

test("Sales Table Exists", function() {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SHOW TABLES LIKE 'sales'");
    return $stmt->rowCount() > 0;
});

test("Stock Inventory Table Exists", function() {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SHOW TABLES LIKE 'stock_inventory'");
    return $stmt->rowCount() > 0;
});

echo "</div>";

// ========== CLASS TESTS ==========
echo "<div class='test-section'><h2>2. Class Instantiation Tests</h2>";

test("User Class Instantiation", function() {
    $user = new User();
    return $user !== null;
});

test("Product Class Instantiation", function() {
    $product = new Product();
    return $product !== null;
});

test("Stock Class Instantiation", function() {
    $stock = new Stock();
    return $stock !== null;
});

test("Sale Class Instantiation", function() {
    $sale = new Sale();
    return $sale !== null;
});

test("Customer Class Instantiation", function() {
    $customer = new Customer();
    return $customer !== null;
});

test("Supplier Class Instantiation", function() {
    $supplier = new Supplier();
    return $supplier !== null;
});

test("Report Class Instantiation", function() {
    $report = new Report();
    return $report !== null;
});

echo "</div>";

// ========== USER TESTS ==========
echo "<div class='test-section'><h2>3. User Management Tests</h2>";

test("Get All Users", function() {
    $user = new User();
    $result = $user->getAll(1, 10);
    return is_array($result);
});

test("Get User Count", function() {
    $user = new User();
    $count = $user->getTotalCount();
    return is_numeric($count) && $count >= 0;
});

test("Admin User Exists", function() {
    $user = new User();
    $users = $user->getAll(1, 100);
    $adminExists = false;
    foreach ($users as $u) {
        if ($u['username'] === 'admin' && $u['role'] === 'admin') {
            $adminExists = true;
            break;
        }
    }
    return $adminExists ? true : "Admin user not found - check database";
});

test("User Login Function", function() {
    $user = new User();
    // Test with default admin credentials
    $result = $user->login('admin', 'password');
    return $result['success'] === true ? true : "Login failed - check password hash";
});

echo "</div>";

// ========== PRODUCT TESTS ==========
echo "<div class='test-section'><h2>4. Product Management Tests</h2>";

test("Get All Products", function() {
    $product = new Product();
    $result = $product->getAll(1, 10);
    return is_array($result);
});

test("Get Product Count", function() {
    $product = new Product();
    $count = $product->getTotalCount();
    return is_numeric($count) && $count >= 0;
});

test("Product Search Function", function() {
    $product = new Product();
    $result = $product->getAll(1, 10, 'test');
    return is_array($result);
});

echo "</div>";

// ========== STOCK TESTS ==========
echo "<div class='test-section'><h2>5. Stock Management Tests</h2>";

test("Get All Stock", function() {
    $stock = new Stock();
    $result = $stock->getAllStock(null, 1, 10);
    return is_array($result);
});

test("Get Low Stock Items", function() {
    $stock = new Stock();
    $result = $stock->getLowStock();
    return is_array($result);
});

test("Get Near Expiry Items", function() {
    $stock = new Stock();
    $result = $stock->getNearExpiry(null, 30);
    return is_array($result);
});

echo "</div>";

// ========== CUSTOMER TESTS ==========
echo "<div class='test-section'><h2>6. Customer Management Tests</h2>";

test("Get All Customers", function() {
    $customer = new Customer();
    $result = $customer->getAll(1, 10);
    return is_array($result);
});

test("Get Customer Count", function() {
    $customer = new Customer();
    $count = $customer->getTotalCount();
    return is_numeric($count) && $count >= 0;
});

echo "</div>";

// ========== SALES TESTS ==========
echo "<div class='test-section'><h2>7. Sales Management Tests</h2>";

test("Get All Sales", function() {
    $sale = new Sale();
    $result = $sale->getAll(1, 10);
    return is_array($result);
});

test("Get Sales Count", function() {
    $sale = new Sale();
    $count = $sale->getTotalCount();
    return is_numeric($count) && $count >= 0;
});

test("Get Sales Summary", function() {
    $sale = new Sale();
    $result = $sale->getSalesSummary();
    return is_array($result) && isset($result['total_sales']);
});

echo "</div>";

// ========== REPORT TESTS ==========
echo "<div class='test-section'><h2>8. Reports Tests</h2>";

test("Sales Report Generation", function() {
    $report = new Report();
    $result = $report->getSalesReport();
    return is_array($result);
});

test("Top Products Report", function() {
    $report = new Report();
    $result = $report->getTopProducts(null, null, null, 10);
    return is_array($result);
});

test("Stock Valuation Report", function() {
    $report = new Report();
    $result = $report->getStockValuation();
    return is_array($result);
});

test("Profit Loss Report", function() {
    $report = new Report();
    $result = $report->getProfitLoss();
    return is_array($result) && isset($result['total_revenue']);
});

echo "</div>";

// ========== HELPER FUNCTION TESTS ==========
echo "<div class='test-section'><h2>9. Helper Function Tests</h2>";

test("Format Currency Function", function() {
    $result = formatCurrency(1234.56);
    return !empty($result) && is_string($result);
});

test("Generate Invoice Number", function() {
    $invoice = generateInvoiceNumber();
    return !empty($invoice) && strpos($invoice, 'INV-') === 0;
});

test("Generate PO Number", function() {
    $po = generatePONumber();
    return !empty($po) && strpos($po, 'PO-') === 0;
});

test("Unit Conversion Function", function() {
    // Test kg to g conversion
    $result = convertUnit(1, 'kg', 'g');
    return $result == 1000;
});

echo "</div>";

// ========== FILE STRUCTURE TESTS ==========
echo "<div class='test-section'><h2>10. File Structure Tests</h2>";

test("Config Files Exist", function() {
    return file_exists('config/config.php') && file_exists('config/database.php');
});

test("Class Files Exist", function() {
    $classes = ['User.php', 'Product.php', 'Stock.php', 'Sale.php', 'Customer.php', 'Supplier.php', 'Report.php'];
    foreach ($classes as $class) {
        if (!file_exists("classes/$class")) {
            return "Missing: classes/$class";
        }
    }
    return true;
});

test("API Files Exist", function() {
    $apis = ['products.php', 'sales.php', 'stock.php', 'customers.php', 'reports.php', 'search.php'];
    foreach ($apis as $api) {
        if (!file_exists("api/$api")) {
            return "Missing: api/$api";
        }
    }
    return true;
});

test("Frontend Pages Exist", function() {
    $pages = ['index.php', 'login.php', 'products.php', 'pos.php', 'stock.php', 'sales.php', 'customers.php', 'reports.php'];
    foreach ($pages as $page) {
        if (!file_exists($page)) {
            return "Missing: $page";
        }
    }
    return true;
});

test("Assets Directory Exists", function() {
    return is_dir('assets/css') && is_dir('assets/js');
});

echo "</div>";

// ========== DISPLAY RESULTS ==========
foreach ($tests as $test) {
    echo "<div class='test-item {$test['status']}'>";
    echo "<strong>{$test['name']}</strong>: {$test['message']}";
    echo "</div>";
}

// ========== SUMMARY ==========
echo "<div class='summary'>
    <h2>Test Summary</h2>
    <div class='stats'>
        <div class='stat-box success'>
            <h3>$passed</h3>
            <p>Passed</p>
        </div>
        <div class='stat-box failure'>
            <h3>$failed</h3>
            <p>Failed</p>
        </div>
        <div class='stat-box warning'>
            <h3>$warnings</h3>
            <p>Warnings</p>
        </div>
    </div>
    <p><strong>Total Tests:</strong> " . count($tests) . "</p>";

if ($failed == 0 && $warnings == 0) {
    echo "<p style='color: #28a745; font-size: 1.2em; font-weight: bold;'>✅ All tests passed! System is ready to use.</p>";
} else if ($failed == 0) {
    echo "<p style='color: #ffc107; font-size: 1.2em; font-weight: bold;'>⚠️ Some warnings detected. System should work but review warnings.</p>";
} else {
    echo "<p style='color: #dc3545; font-size: 1.2em; font-weight: bold;'>❌ Some tests failed. Please fix the issues before using the system.</p>";
}

echo "</div>";

echo "</div></body></html>";
?>

