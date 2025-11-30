<?php
/**
 * Functional Test Script
 * Tests actual functionality with sample data
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

// Set session variables (session already started in config.php)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 1;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Functional Tests - Tyazubwenge Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #0d6efd; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .test-item { padding: 10px; margin: 5px 0; background: white; border-left: 4px solid #ccc; }
        .test-item.pass { border-left-color: #28a745; }
        .test-item.fail { border-left-color: #dc3545; }
        .pass::before { content: '✓ '; color: #28a745; font-weight: bold; }
        .fail::before { content: '✗ '; color: #dc3545; font-weight: bold; }
        .info { color: #666; font-size: 0.9em; margin-left: 25px; }
    </style>
</head>
<body>
    <div class='test-container'>
        <h1>⚙️ Functional Tests</h1>";

$testResults = [];

function runTest($name, $callback) {
    global $testResults;
    try {
        $result = $callback();
        $testResults[] = [
            'name' => $name,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? ($result['success'] ? 'OK' : 'Failed')
        ];
    } catch (Exception $e) {
        $testResults[] = [
            'name' => $name,
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// ========== PRODUCT FUNCTIONALITY ==========
echo "<div class='test-section'><h2>1. Product Management Functionality</h2>";

runTest("Create Test Product", function() {
    $product = new Product();
    $data = [
        'name' => 'Test Product ' . time(),
        'sku' => 'TEST-' . rand(1000, 9999),
        'barcode' => '1234567890',
        'category_id' => null,
        'brand_id' => null,
        'description' => 'Test product for functionality testing',
        'unit' => 'g',
        'retail_price' => 100.00,
        'wholesale_price' => 80.00,
        'cost_price' => 50.00,
        'min_stock_level' => 10,
        'status' => 'active'
    ];
    $result = $product->create($data);
    return [
        'success' => $result['success'] ?? false,
        'message' => $result['success'] ? "Product created with ID: " . ($result['id'] ?? 'N/A') : ($result['message'] ?? 'Failed'),
        'product_id' => $result['id'] ?? null
    ];
});

runTest("Update Product", function() {
    $product = new Product();
    $products = $product->getAll(1, 1);
    if (empty($products)) {
        return ['success' => false, 'message' => 'No products to update'];
    }
    $prod = $products[0];
    $data = [
        'name' => $prod['name'] . ' (Updated)',
        'sku' => $prod['sku'],
        'barcode' => $prod['barcode'],
        'category_id' => $prod['category_id'],
        'brand_id' => $prod['brand_id'],
        'description' => $prod['description'],
        'unit' => $prod['unit'],
        'retail_price' => $prod['retail_price'],
        'wholesale_price' => $prod['wholesale_price'],
        'cost_price' => $prod['cost_price'],
        'min_stock_level' => $prod['min_stock_level'],
        'status' => $prod['status']
    ];
    $result = $product->update($prod['id'], $data);
    return ['success' => $result, 'message' => $result ? 'Product updated successfully' : 'Update failed'];
});

echo "</div>";

// ========== STOCK FUNCTIONALITY ==========
echo "<div class='test-section'><h2>2. Stock Management Functionality</h2>";

runTest("Add Stock to Product", function() {
    $product = new Product();
    $products = $product->getAll(1, 1);
    if (empty($products)) {
        return ['success' => false, 'message' => 'No products available'];
    }
    $prod = $products[0];
    
    $stock = new Stock();
    $result = $stock->addStock($prod['id'], 1, 100, 'g', null, null, null);
    return [
        'success' => $result,
        'message' => $result ? 'Stock added successfully' : 'Failed to add stock'
    ];
});

runTest("Get Stock for Product", function() {
    $product = new Product();
    $products = $product->getAll(1, 1);
    if (empty($products)) {
        return ['success' => false, 'message' => 'No products available'];
    }
    $prod = $products[0];
    
    $stock = new Stock();
    $stockData = $stock->getStock($prod['id'], 1);
    return [
        'success' => $stockData !== false,
        'message' => $stockData ? "Stock found: " . $stockData['quantity'] . " " . $stockData['unit'] : 'No stock found'
    ];
});

echo "</div>";

// ========== CUSTOMER FUNCTIONALITY ==========
echo "<div class='test-section'><h2>3. Customer Management Functionality</h2>";

runTest("Create Test Customer", function() {
    $customer = new Customer();
    $data = [
        'name' => 'Test Customer ' . time(),
        'email' => 'test' . time() . '@example.com',
        'phone' => '1234567890',
        'address' => 'Test Address',
        'loyalty_points' => 0,
        'credit_limit' => 1000.00,
        'status' => 'active'
    ];
    $result = $customer->create($data);
    return [
        'success' => $result['success'] ?? false,
        'message' => $result['success'] ? "Customer created with ID: " . ($result['id'] ?? 'N/A') : ($result['message'] ?? 'Failed')
    ];
});

echo "</div>";

// ========== SALES FUNCTIONALITY ==========
echo "<div class='test-section'><h2>4. Sales Functionality</h2>";

runTest("Create Test Sale", function() {
    $product = new Product();
    $products = $product->getAll(1, 1);
    if (empty($products)) {
        return ['success' => false, 'message' => 'No products available for sale'];
    }
    $prod = $products[0];
    
    // Check stock
    $stock = new Stock();
    $stockData = $stock->getStock($prod['id'], 1);
    if (!$stockData || $stockData['quantity'] < 1) {
        // Add stock first
        $stock->addStock($prod['id'], 1, 10, $prod['unit'], null, null, null);
        $stockData = $stock->getStock($prod['id'], 1);
    }
    
    $sale = new Sale();
    $saleData = [
        'customer_id' => null,
        'branch_id' => 1,
        'user_id' => 1,
        'total_amount' => $prod['retail_price'],
        'discount' => 0,
        'tax' => 0,
        'final_amount' => $prod['retail_price'],
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'sale_type' => 'retail',
        'notes' => 'Test sale',
        'items' => [[
            'product_id' => $prod['id'],
            'quantity' => 1,
            'unit' => $prod['unit'],
            'unit_price' => $prod['retail_price'],
            'discount' => 0,
            'subtotal' => $prod['retail_price']
        ]]
    ];
    
    $result = $sale->create($saleData);
    return [
        'success' => $result['success'] ?? false,
        'message' => $result['success'] ? "Sale created: " . ($result['invoice_number'] ?? 'N/A') : ($result['message'] ?? 'Failed')
    ];
});

echo "</div>";

// ========== REPORT FUNCTIONALITY ==========
echo "<div class='test-section'><h2>5. Report Generation Functionality</h2>";

runTest("Generate Sales Report", function() {
    $report = new Report();
    $result = $report->getSalesReport(null, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
    return [
        'success' => is_array($result),
        'message' => is_array($result) ? "Report generated with " . count($result) . " records" : 'Failed'
    ];
});

runTest("Generate Top Products Report", function() {
    $report = new Report();
    $result = $report->getTopProducts(null, null, null, 5);
    return [
        'success' => is_array($result),
        'message' => is_array($result) ? "Top " . count($result) . " products retrieved" : 'Failed'
    ];
});

echo "</div>";

// ========== DISPLAY RESULTS ==========
foreach ($testResults as $test) {
    echo "<div class='test-item " . ($test['success'] ? 'pass' : 'fail') . "'>";
    echo "<strong>{$test['name']}</strong><br>";
    echo "<span class='info'>{$test['message']}</span>";
    echo "</div>";
}

$passed = count(array_filter($testResults, fn($t) => $t['success']));
$total = count($testResults);

echo "<div style='margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;'>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Passed:</strong> $passed / $total</p>";
if ($passed == $total) {
    echo "<p style='color: #28a745; font-weight: bold;'>✅ All functional tests passed!</p>";
} else {
    echo "<p style='color: #dc3545; font-weight: bold;'>❌ Some tests failed. Review the results above.</p>";
}
echo "</div>";

echo "</div></body></html>";
?>

